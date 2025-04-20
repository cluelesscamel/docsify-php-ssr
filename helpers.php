<?php

include 'constants.php';

function cleanMarkdown($markdown, $lang) {
	$markdown = preg_replace_callback(
		'/\((\/[^\)]+)\)/',
		function ($matches) use ($lang) {
			//don't touch image links
			if (strpos($matches[1], '/_media/') === 0) return $matches[0];

			$path = $matches[1];
			
			//always remove the .md section from the path
			$path = preg_replace('/\.md$/', '', $path);

			//if the path starts with /, add the language section
			//don't prepend the language if it's already there
			if (strpos($path, '/') === 0 && strpos($path, "/$lang/") !== 0) {
				$path = "$lang$path";
			}

			return "($path)";
		},
		$markdown
	);

	// Prepend DOCS_SOURCE to links starting with "/_media/"
	$markdown = preg_replace_callback(
		'/\((\/_media\/[^\)]+)\)/',
		function ($matches) {
			return '(' . DOCS_SOURCE . $matches[1] . ')';
		},
		$markdown
	);

	return $markdown;
}

function getDocMarkdownFromFile($path) {
	//get correct file from the /docs folder
	$filePath = DOCS_SOURCE . $path;

	// Check if the file exists
	// If it doesn't, return a 404 response
	if (!file_exists($filePath)) {
		http_response_code(404);
		echo "File not found: " . $path;
		exit;
	}

	// Get the file content
	if (strpos($path, '.md') !== 0) {
		$response = file_get_contents($filePath);

		//add the language section from $path to any links in the text that start with "/" but not "/_media/"
		$lang = strtok($path, '/');
		if (!in_array($lang, ['fr', 'en', 'de'])) $lang = '';

		return cleanMarkdown($response, $lang);
	} else {
		return file_get_contents($filePath);
	}
}

?>
