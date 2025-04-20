<?php
	//this php file will load the correct markdown file
	//fix image links and inject its rendered html into the body
	//this will make bots/crawlers happy
	//on the client, docsify will just take over and load/render markdown files as usual

	include 'constants.php';
	include 'helpers.php';
	include 'Parsedown.php';

	//get the markdown file return the rendered html
	function getRenderedMarkdown($filePath) {
		$markdown = getDocMarkdownFromFile($filePath);

		if (!$markdown) {
			echo "File not found: " . $filePath;
			return;
		}

		$parsedown = new Parsedown();
		$htmlContent = $parsedown->text($markdown);

		return $htmlContent;
	}

	//render the html for the requested path
	function renderPage() {
		// Get the requested file path from the URL, without any query parameters
		$requestUri = strtok($_SERVER['REQUEST_URI'], '?');

		//detect the language from the first part of the path
		$lang = strtok($requestUri, '/');
		if (!in_array($lang, ['nl', 'fr', 'en', 'de'])) $lang = '';

		//determine the full file path
		$filePath = $requestUri;
		//if the request is for the root, just load the index file
		if ($filePath === '/') $filePath = '/index';
		//add extension if missing
		if (strpos($filePath, '.md') === false) $filePath .= '.md';

		//get the markdown content
		$htmlContent = getRenderedMarkdown($filePath);

		//get the sidebar from the repo as well
		$sidebarPath = '/_sidebar.md';	
		if ($lang) $sidebarPath = '/' . $lang . '/_sidebar.md';

		$sidebarContent = getRenderedMarkdown($sidebarPath);

		//mimic the docsify layout
		echo '<main>';
		echo '<aside class="sidebar">' . $sidebarContent . '</aside>';
		echo '<section class="content">';
		echo '<article class="markdown-section" id="main">'. $htmlContent . '</article>';
		echo '</section>';
		echo '</main>';
	}

	//get a link to the same page in a different language
	function getLangLink($lang) {
		$domain = $_SERVER['HTTP_HOST'];
		$href = $_SERVER['REQUEST_URI'];

		$langPart = '/' . $lang;
		if ($lang === 'nl') $langPart = '';

		//if href starts with /nl/ or /en/ or /fr/ or /de/, replace it with the new lang
		//otherwise just add the lang to the start of the href
		if (preg_match('/\/(fr|en|de|nl)\//', $href)) {
			$href = preg_replace('/\/(fr|en|de|nl)\//', $langPart . '/', $href);
		} else {
			$href = $langPart . $href;
		}

		return 'https://' . $domain . $href;
	}
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">

		<title>PHP Docsify SSR</title>

		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />

		<meta name="description" content="PHP Docsify SSR" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">

		<link rel="stylesheet" href="//cdn.jsdelivr.net/npm/docsify@4/lib/themes/vue.css" title="vue" />

		<link rel="alternate" hreflang="nl" href="<?php echo getLangLink('nl'); ?>" />
		<link rel="alternate" hreflang="en" href="<?php echo getLangLink('en'); ?>" />
		<link rel="alternate" hreflang="fr" href="<?php echo getLangLink('fr'); ?>" />
		<link rel="alternate" hreflang="de" href="<?php echo getLangLink('de'); ?>" />
	</head>
	<body>
		<div id="app">
			<?php renderPage(); ?>
		</div>

		<script>
		// Set html "lang" attribute based on URL
		var lang = location.hash.match(/#\/(nl|fr|en|de)\//);
		if (lang) document.documentElement.setAttribute('lang', lang[1]);

		//rewrite the url if the user went to a server rendered page
		//user will have wanted to visit /some/page for example, we need to rewrite the url to /#/some/page
		if (location.pathname !== '/') {
			const path = location.pathname.replace(/\/$/, '');
			window.location.href = window.location.origin + '/#' + path;
		}

		function render() {
			window.$docsify = {
				name: "PHP Docsify SSR",
				homepage: 'index.md',
				basePath: '/docs/',
				notFoundPage: {
					'/': '_404.md',
					'/fr': 'fr/_404.md',
					'/en': 'en/_404.md',
					'/de': 'de/_404.md',
				},
				loadNavbar: true,
				loadSidebar: true,
				repo: '',
				alias: {
					'/.*/_navbar.md': '/_navbar.md',
					'/en/.*/_sidebar.md': '/en/_sidebar.md',
					'/fr/.*/_sidebar.md': '/fr/_sidebar.md',
					'/de/.*/_sidebar.md': '/de/_sidebar.md',
					'/(?!en|fr|de).*/_sidebar.md': '/_sidebar.md',
				},
				noCompileLinks: [
					'/_navbar.md',
					'/_sidebar.md',
				],

				plugins: [
					function(hook, vm) {
						hook.beforeEach(function(markdown) {
							//fix links to go to pages of the same language
							//fix image paths all load from _media folder
							markdown = markdown.replace(/(!?)\[(.*?)\]\((.*?)\)/g, function(match, img, text, url) {
								//don't touch absolute paths
								if (url.indexOf('http') === 0) return match;

								//return image markdown
								if (img) {
									//replace anything that comes before _media with website root
									const basePath = window.$docsify.basePath,
										absoluteImagePath = url.replace(/.*?_media\//, basePath + '_media/');

									return '![' + text + '](' + absoluteImagePath + ')';
								}

								//otherwise return link markdown with absolute path
								//figure out the lang part from URL
								const lang = location.hash.match(/#\/(en|fr|de)\//)?.[1],
									absolutePath = (lang || '') + url;

								return '[' + text + '](' + absolutePath + ')';
							});

							return markdown;
						})

						hook.doneEach(function() {
							//wait a bit for the page to render so querySelector doesn't select old elements
							setTimeout(function() {
								//replace the language part of the url when a language link in app-nav is clicked
								const langLinks = document.querySelectorAll('.app-nav ul a');

								let lang = location.hash.match(/#\/(en|fr|de)\//)?.[1];
								if (!lang) lang = 'nl';

								for (const link of langLinks) {
									link.addEventListener('click', function(e) {
										e.preventDefault();

										const lang = location.hash.match(/#\/(en|fr|de)\//)?.[1],
											newLang = e.target.href.match(/(en|fr|de)\//)?.[1];

										//do nothing when language is the same
										if (!lang && !newLang) return;

										//if both lang and newLang are defined
										if (lang && newLang) location.hash = location.hash.replace(lang, newLang);

										//if no lang (= base language)
										else if (!lang) location.hash = location.hash.replace('#/', '#/' + newLang + '/');

										//if reverting to base language, just remove the lang part
										else location.hash = location.hash.replace('/' + lang, '');
									});

									//add an active class to the active language
									if (link.text.toLowerCase() == lang.toLowerCase()) {
										link.classList.add('active');
									} else {
										link.classList.remove('active');
									}
								}
							}, 100);
						})

						hook.doneEach(function() {
							//docsify manipulates image urls to be relative to the current page
							//but saves the original in a data-origin property on the image.
							//set all image src properties back to that data-origin property
							//if it contains fetch.php
							const images = document.querySelectorAll('.content img');

							for (const img of images) {
								const origin = img.getAttribute('data-origin');
								if (origin) img.src = origin;
							}
						})
					}
				]
			}
		}

		render();

		</script>
		<!-- Docsify v4 -->
		<script src="//cdn.jsdelivr.net/npm/docsify@4/lib/docsify.min.js"></script>
		<script src="//cdn.jsdelivr.net/npm/vue@2/dist/vue.min.js"></script>
	</body>
</html>
