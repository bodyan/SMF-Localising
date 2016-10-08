<?php

class SMFLocale {

	/**
	 * Get array of names of the files included in directory
	 *
	 * @param (string) where $folder - name of folder
	 * @author bodyan <bodyanua@gmail.com>
	 * @return array() 
	 */
	public function getFileNames($folder){
		$files = scandir($folder);
		if(is_array($files)){
				sort($files);
		}else{
			return "$folder - не являється каталог";
		}
		//видаляємо значення з крапками
		$files = array_slice($files, 2);
		return $files;
	return false;
	}

	/**
	 * Indentify language of file from name using regular expressions
	 * for this we use first file from array of files getFileNames()
	 *
	 * @param (string) where $folder - name of folder
	 * @author bodyan <bodyanua@gmail.com>
	 * @return (string) return language of files
	 */
	public function fileLang ($value=''){
		$language = preg_replace('/(?:^\w+.)(.+).php/', '$1', $this->getFileNames($value)[0]);
		return $language;
	}
	/**
	 * return name of file, example Admin.ukrainian-utf8.php => 'Admin'
	 *
	 * @param (string) where $value - name of file
	 * @author bodyan <bodyanua@gmail.com>
	 * @return (string) return name of file
	 */
	public function fileName ($value=''){
		if (!is_array($value)) {
			return preg_replace('/(^\w+)(?:.+.php)/', '$1', $value);
		} else {
			return "Назва не може бути масивом";
		}
		return false;
	}

	/**
	 * depending of operating system we have different slash, for Windows '/', Linux - '\'
	 *
	 * @param () nothing, 
	 * @author bodyan <bodyanua@gmail.com>
	 * @return (array) return string 
	 */
	public function slash() {
	$ua = $_SERVER['SERVER_SOFTWARE'];

		if (strpos($ua, 'Win32') !== false) {
		    return '/';
		} else {
			return '\\';
		}
	return false;
	}

	/**
	 * with this method we receive array from file of data : names of vaiables, index and their value
	 * @param (string) where $name - name of file
	 * @author bodyan <bodyanua@gmail.com>
	 * @return (array) return array of strings
	 */
	public function readfile($name) {
		$file = fopen($name, "r") or exit("Unable to open file!");
		
		$result = array();
		$multi_line = false;
	  	//pattern for getting row if it starts from $txt['original'] or txtBirthdayEmails['original']
		$pattern = array(
		'sline' => '/(^\$.+\[\'.+\'\])(?:|\s)=.+\;/', 		//single line pattern
		'mline_start' => '/^(\$.+\[\'.+\'\])(?:|\s)=.+/', 	//multi-line start pattern
		'mline_end' => '/^[^$\s].+\'\;/', 					//multi-line end pattern
		'commented' => '/(^\/\/\$txt.+\'\;)/', 				//commented variable
		'name' => '/(?:^\$.+\[\'(.+)\'\])/', 				//get name of variable, ex. $txt['some_name'] -> 'some_name'
		/** get name and value of variable, divided in two group. For ex. $txt['some_name'] = 'some_text';
		* group 1: variable -> '$txt'
		* group 2: index -> 'some_name'
		* group 3: value -> 'some_text'
		*/
		'sline_value' => '/(?:(^\$.+)\[\'(.+)\'\])*\s=(?:\s|)(.+)\;/',
		'mline_value' => '/^((\$.+)\[\'(.+)\'\])(?:|\s)=(.+)/', //same as sline_value, except groups - 2,3
		'variable' => '/^(\$.+)\[\'.+\'\](?:|\s)=.+/'		//get variable, ex. $txt['some_name'] => $txt

		);

		while(!feof($file)) {
			 // get every row
		  	  $subject = fgets($file);
			  if (preg_match($pattern['sline'], $subject, $matches)) {
			  //single-line string
			  	if(preg_match($pattern['sline_value'], $subject, $matches)) {
			  	$variable = $matches[1];
			  	$index = $matches[2];
			  	$value = $matches[3];
			  	$result[$index] = array('variable' => $variable, 'value' => $value);
			  	$multi_line = false;
			  	} else {
			  		$result['помилка'] = $subject;
			  	}
			  } elseif (preg_match($pattern['mline_start'], $subject, $matches) && !preg_match($pattern['sline'], $subject, $matches) && !preg_match($pattern['commented'], $subject, $matches)) {
			  //multi-line string
			  	$string = preg_match($pattern['mline_value'], $subject, $matches);
			  	$variable = $matches[2];
			  	$index = $matches[3];
			  	$value = $matches[4];
			  	$result[$index]['variable'] = $variable; 
				$result[$index]['value'][] = $value; 
			  	$multi_line = true;
			  } elseif (preg_match($pattern['mline_end'], $subject, $matches) && !preg_match($pattern['commented'], $subject, $matches)) {
			  	//end of multi-line string,
			  	$result[$index]['value'][] = $subject;
			  	$multi_line = false;
			  	$index = false;
			  } elseif ($multi_line) {
			  	//content of multi-line variable
			  	$result[$index]['value'][] = $subject;
			  } elseif (preg_match($pattern['commented'], $subject, $matches)) {
			  	//commented variable, must be (here!!!) before end of multi-line string
			  	$result[]['value'] = $subject;
			  }	else {
			  	//comments and other not necessary strings go's here
			  	$result[]['value'] = $subject;
			  }


		  }
		fclose($file);
		return $result;
	}
	/**
	 * this method is for pasing and creating a file from two arrays
	 * if in the file with translation is present variable with same index, 
	 * that in english file we make replace. 
	 * After parsing and replacing will be created file with name of translation 
	 * @param (array) 
	 * @author bodyan <bodyanua@gmail.com>
	 * 
	 */
	public function writefile($english = '', $translation = '', $filename) {
		
		if (!is_array($english) || !is_array($translation)) 
			exit("Error creating file. Data must be array ");
		$w = fopen($filename, "w") or exit("Unable create file - $filename!");
		$row = '';
		foreach ($english as $key1 => $data1) {
			//if index not integer (spaces and comments) or not array(multi-line) then this is single line - let's translate this
			if (!is_int($key1) && count($data1['value']) == 1) {
				$row = $data1['variable'].'[\''.$key1 . '\'] = ';
				$row .= (isset($translation[$key1]) &&(!is_array($translation[$key1]['value']))) ? $translation[$key1]['value'] . ';' . PHP_EOL : $data1['value'] .';'. PHP_EOL;
			//if it's array then we have multi-line 
			} elseif(count($data1['value']) > 1) {
				$row = $data1['variable'].'[\''.$key1 . '\'] = ';
				if (array_key_exists($key1, $translation) && count($data1['value']) == count($translation[$key1]['value']) ) {
					foreach ($translation[$key1]['value'] as $num => $data2) {
						$row .= ($num == 0) ? $data2 : $data2;
					}
				} else {
					foreach ($data1['value'] as $num1 => $data3) {
						$row .= ($num1 == 0) ? $data3  . PHP_EOL : $data3;
					}
				}
			} else {
				$row = $data1['value'];
			}
			fputs($w, $row);
		}
		fclose($w);
	}
	
	/**
	 * final method created for batch opening folders and reading files,
	 * parsing, replacing and creating files in final folder
	 * @param (array) 
	 * @author bodyan <bodyanua@gmail.com>
	 * 
	 */	
	public function completeTranslation ($english = '', $input = '', $output = '') {
		if ($english !== '' && $input !== '' && $output !== '') {
			foreach ($this->getFileNames($english) as $en_file) {
				$name = $this->fileName($en_file);
				echo '<span style="font: italic bold 14px/30px Georgia, serif; color: green;">'. $name .'</span>';
				if(in_array($this->fileName($en_file).'.'.$this->fileLang($input).'.php', $this->getFileNames($input))) {
				 		  $en_file = $this->readfile(getcwd().'\\'.$english.'\\'.$name.'.'.$this->fileLang($english).'.php');
				 		  echo ' | <span style ="color:red;">reading english file</span> - OK';
				 		  $in_file = $this->readfile(getcwd().'\\'.$input.'\\'.$name.'.'.$this->fileLang($input).'.php');
				 		  echo ' | <span style ="color:brown;">reading '.$this->fileLang($input).' file</span> - OK';
				 		  $filename = getcwd().'\\'.$output.'\\'.$name.'.'.$this->fileLang($input).'.php';
				 		  $this->writefile($en_file, $in_file, $filename);
				 		  echo ' | <span style ="color:blue;">creating file</span> - OK<hr>';
				} else{
				 		  echo ' | <span style ="color:grey;">skipped</span><hr>';
				}
			}
		} else {
			echo "Please enter all folders correctly!!!";
		}	
	}
}


?>