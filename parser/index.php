<!DOCTYPE HTML>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Парсер лендингов</title>
	<link media="screen" href="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.5.0/semantic.min.css" type="text/css" rel="stylesheet">
	<script src="//code.jquery.com/jquery-2.0.3.min.js"></script>
	<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.5.0/semantic.min.js"></script>
</head>
<body>
<div class="ui two column centered grid">

	<div class="four column centered row">
		<div class="column">
			<h2 class="ui icon header">
				<i class="settings icon"></i>
				<div class="content">
					Парсер сайтов
					<div class="sub header">Мы не гарантируем корректность копируемого сайта.</div>
				</div>
			</h2>
		</div>
	</div>

	<div class="column">
		<form class="ui form">
			<div class="field">
				<label>Ссылка на сайт</label>
				<input type="text" name="first-name" placeholder="Например http://saitru.ru" value="<?php echo $_GET["first-name"]?$_GET["first-name"]:""; ?>">
			</div>
			<button class="ui button" type="submit">Парсить</button>
		</form>
		<?php

		$lp = "lp2";
		/*создание папок по путям*/
		function create_path($path,$type='')
		{
			$arr  = explode('/', $path);
			$curr = array();
			foreach ($arr as $key => $val) {
				if (!empty($val)) {
					$curr[] = $val;
					$dir_i  = implode('/', $curr);
					$dir_i = preg_replace("/[\'\"]/", "", $dir_i);
					$dir_c  = "lp/lp1/$dir_i/";
					if (!file_exists($dir_c)) {
						mkdir($dir_c, 0755, true);
						echo 'создаю каталог '.$dir_c.'<br>';
					}
				}
			}
		}
		function get_cont($base)
		{
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_FAILONERROR, 1);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // allow redirects
			curl_setopt($curl, CURLOPT_TIMEOUT, 10); // times out after 4s
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // return into a variable
			curl_setopt($curl, CURLOPT_URL, $base);
			curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.1.5) Gecko/20091102 Firefox/3.5.5 GTB6");
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			$data = curl_exec($curl);
			return $data;
		}
		/*подключаем парсер*/
		echo 'подключаю библиотеку парсера html - ';
		include 'simple_html_dom.php';
		echo '<span style="color:green;">есть</span>'.'<br>';
		echo 'подключаю библиотеку парсера css - ';
		include 'cssparser.php';
		echo '<span style="color:green;">есть</span>'.'<br>';

		if ($_GET['first-name']) {
			$base = $_GET['first-name'];
			echo 'получаю параметр first-name - <span style="color:green;">'.$base.'</span>'.'<br>';
			$base_name = basename($base);
			echo 'определяю корневую директорию сайта - <span style="color:green;">'.$base_name.'</span>'.'<br>';
			$base = preg_replace("#/$#", "", $base);
			$base_url = preg_replace("#".$base_name."#", "", $base);
			echo 'определяю подпапки - <span style="color:green;">'.($base_url?$base_url:'нет').'</span>'.'<br>';
			$base = $base."/";

			echo '<strong>ЗАПУСКАЮ ЗАПАСНОЙ ПАРСЕР</strong>'.'<br>';
			$data = get_cont($base);
			$html = str_get_html($data);

			echo 'читаю содержимое '.$base.' - '.(empty($html)?'<span style="color:red;">нет':'<span style="color:green;">есть').'</span>'.'<br>';

			echo '<strong>ПАРСИМ link css</strong><br>';

			$css_array_find=array();
			$css_array_repl=array();
			foreach ($html->find('link') as $element) {
				$dir_css  = dirname($element->href);
				$url_css  = $base.$element->href;
				$name_css = basename($url_css, ".css");
				if (preg_match("/\.(ico|png|jpeg|gif)/",$name_css)) {
					continue;
				};
				$dir_css='css';
				create_path($dir_css);
				$file = fopen("lp/".$lp."/".$dir_css."/".$name_css.".css", "w+");
				$cont = get_cont($url_css);

				if (preg_match("/main\.css/",$name_css.".css")) {
					preg_match_all("/url\((.*)?\)/Usi", $cont, $arrTable, PREG_SET_ORDER);
					$img_array_find=array();
					$img_array_repl=array();
					foreach ($arrTable as $res) {
						$dir_im       = preg_replace("/\.\.\//", "", $res[1]);
						$dir_im       = preg_replace("/[\'\"]/", "", $dir_im);
						$url_img_css  = $base.$dir_im;
						$dir_img_css  = dirname($dir_im);
						$name_img_css = basename($url_img_css);
						if (preg_match("/data:image/",$dir_img_css) || preg_match("/\.html/",$name_img_css)) {
							continue;
						}
						create_path('img');
						$file2 = fopen("lp/".$lp."/img/".$name_img_css, "w+");
						$cont2 = get_cont($url_img_css);
						array_push($img_array_find,$dir_im);
						array_push($img_array_repl,"../img/".$name_img_css);
						fwrite($file2, $cont2);
						fclose($file2);
						echo 'записываю из css '.$url_img_css.' img '.$name_img_css.' в каталог '.$dir_img_css.'<br>';
					}
					$img_array_find = array_unique($img_array_find);
					$img_array_repl = array_unique($img_array_repl);
					$cont=str_replace($img_array_find,$img_array_repl,$cont);
				};

				if (preg_match("/fonts\.css/",$name_css.".css")) {
					preg_match_all("/url\((.*)?\)/Usi", $cont, $arrTable, PREG_SET_ORDER);
					$font_array_find=array();
					$font_array_repl=array();
					foreach ($arrTable as $res) {
						$dir_im       = preg_replace("/\.\.\//", "", $res[1]);
						$dir_im       = preg_replace("/[\'\"]/", "", $dir_im);
						$url_fonts_css  = $base.$dir_im;
						$dir_fonts_css  = dirname($dir_im);
						$name_font_css = basename($url_fonts_css);

						$parse_url_font = parse_url($url_fonts_css);
						$path_info = pathinfo($parse_url_font['path']);
						$ext = array('ttf','woff','woff2','eot');
						if(!in_array($path_info['extension'],$ext)){
							continue;
						}

						$parse_url_font['path'] = preg_replace("/\/".$base_name."/",'',$parse_url_font['path']);
						create_path('fonts');
						$file2 = fopen("lp/".$lp."/fonts/".$name_font_css, "w+");
						$cont2 = get_cont($url_fonts_css);
						array_push($font_array_find,$dir_im);
						array_push($font_array_repl,"../fonts/".$name_font_css);
						fwrite($file2, $cont2);
						fclose($file2);
						echo 'записываю fonts из css '.$url_fonts_css.' fonts '.$name_font_css.' в каталог '.'lp/'.$lp.'/fonts/'.'<br>';
					}
					$font_array_find = array_unique($font_array_find);
					$font_array_repl = array_unique($font_array_repl);
					$cont=str_replace($font_array_find,$font_array_repl,$cont);
				};

				fwrite($file, $cont);
				fclose($file);
				array_push($css_array_find,$element->href);
				array_push($css_array_repl,$dir_css.'/'.$name_css.'.css');
				echo 'записываю из каталога '.$url_css.' файл css '.$name_css.'.css в каталог '.$dir_css.'<br>';
			}

			echo '<strong>ПАРСИМ script</strong><br>';

			$js_array_find=array();
			$js_array_repl=array();
			foreach ($html->find('script') as $element) {
				if (preg_match("/http[s]?\:/",$element->src)) {
					$url_js=$element->src;
				}else{
					$url_js=$base.$element->src;
				}
				$name_js = basename($url_js);
				$name_js = preg_replace("/\?.+$/","",$name_js);
				if (!stripos($name_js, '.js')) {
					continue;
				}
				$dir_js='js';
				create_path($dir_js);
				$cont = get_cont($url_js);
				$file = fopen("lp/".$lp."/".$dir_js."/".$name_js, "w+");
				fwrite($file, $cont);
				fclose($file);
				array_push($js_array_find,$element->src);
				array_push($js_array_repl,$dir_js.'/'.$name_js);
				echo 'записываю из '.$url_js.' файл js '.$name_js.' в каталог '.$dir_js.'<br>';

			}

			echo '<strong>ПАРСИМ img</strong><br>';

			$img_array_find=array();
			$img_array_repl=array();
			foreach ($html->find('img') as $element) {
				if (preg_match("/data:image/",$element->src)) {
					continue;
				}
				if (preg_match("/http[s]?\:/",$element->src)) {
					$url_img=$element->src;
				}else{
					$url_img=$base.$element->src;
				}
				$url_img=str_replace("\\","/",$url_img);
				$name_img = basename($url_img);
				$dir_img='img';
				create_path($dir_img);
				$file = fopen("lp/$lp/".$dir_img."/".$name_img, "w+");
				$cont = get_cont($url_img);
				fwrite($file, $cont);
				fclose($file);
				array_push($img_array_find,$element->src);
				array_push($img_array_repl,$dir_img.'/'.$name_img);
				echo 'записываю из '.$url_img.' файл img '.$name_img.' в каталог '.$dir_img.'<br>';
			}

			$css_array_find = array_unique($css_array_find);
			$css_array_repl = array_unique($css_array_repl);
			$data=str_replace($css_array_find,$css_array_repl,$data);

			$js_array_find = array_unique($js_array_find);
			$js_array_repl = array_unique($js_array_repl);
			$data=str_replace($js_array_find,$js_array_repl,$data);

			$img_array_find = array_unique($img_array_find);
			$img_array_repl = array_unique($img_array_repl);
			$data=str_replace($img_array_find,$img_array_repl,$data);

			$file = fopen("lp/$lp/index.html", "w");
			fwrite($file, $data);
			fclose($file);

		} else {
			echo 'параметр не задан'.'<br>';
		}
		?>
	</div>
</div>
</body>
</html>
<?php exit(); ?>
