<?php

class XFI_BbCode_Imdb
{
	public static function tagImdb(array $tag, array $rendererStates, XenForo_BbCode_Formatter_Base $formatter)
	{
		preg_match('/tt\d{5,7}/', $tag['children'][0], $id);
		$id = filter_var($id[0], FILTER_SANITIZE_STRING);

		if (is_string($id) && !empty($id)) {
			return XFI_BbCode_Imdb::getRating($id);
		} else {
			return $tag['children'][0];
		}
	}

	public static function buildEmbed($mediaKey, array $site, $siteId)
	{
		preg_match('/tt\d{5,7}/', $mediaKey, $id);
		$id = filter_var($id[0], FILTER_SANITIZE_STRING);

		if (is_string($id) && !empty($id)) {
			return XFI_BbCode_Imdb::getRating($id);
		} else {
			return $mediaKey;
		}
	}

	private static function getRating($id)
	{
		$options = XenForo_Application::getOptions();

		$imdb_rating_dir = XenForo_Helper_File::getExternalDataPath() . '/imdb/';
		$imdb_render_dir = dirname(__FILE__) . '/ImdbSource/' . $options->xfiImdbTheme . '/';
		$imdb_cache_time = ($options->xfiImdbCache) ? 86400 * $options->xfiImdbCache : 0;
		$imdb_sitename   = ($options->xfiImdbSitename) ? $options->xfiImdbSitename : '';

		$image = imagecreatefrompng($imdb_render_dir . 'na_imdb.png');
		$font = $imdb_render_dir . 'font.ttf';

		if (!file_exists($imdb_rating_dir . $options->xfiImdbTheme . $id . '.png') || (time() - filemtime($imdb_rating_dir . $options->xfiImdbTheme . $id . '.png')) > $imdb_cache_time) {
			$xml = file_get_contents('http://www.imdb.com/title/' . $id);
			preg_match_all('#<span itemprop="ratingValue">(.*?)</span>.*?<span class="small" itemprop="ratingCount">(.*?)</span>#isu', $xml, $imdb, PREG_SET_ORDER);
			$imdb_rating = $imdb[0][1];
			$imdb_votes  = $imdb[0][2];

			if ($imdb_rating != 0) {
				$image  = imagecreatefrompng($imdb_render_dir . 'back_imdb.png');
				$star   = imagecreatefrompng($imdb_render_dir . 'star.png');
				$color  = imagecolorallocate($image, 190, 190, 190);
				$r_font = imagecreatefrompng($imdb_render_dir . 'rating_font.png');
				$v_font = imagecreatefrompng($imdb_render_dir . 'votes_font.png');
				$rating = explode('.', $imdb_rating);

				switch (end($rating)) {
					case '0': $symbol = 0; break;
					case '1': $symbol = 10; break;
					case '2': $symbol = 20; break;
					case '3': $symbol = 30; break;
					case '4': $symbol = 40; break;
					case '5': $symbol = 50; break;
					case '6': $symbol = 60; break;
					case '7': $symbol = 70; break;
					case '8': $symbol = 80; break;
					case '9': $symbol = 90; break;
				}
				switch (reset($rating)) {
					case '0': $symbol2 = 0; break;
					case '1': $symbol2 = 10; break;
					case '2': $symbol2 = 20; break;
					case '3': $symbol2 = 30; break;
					case '4': $symbol2 = 40; break;
					case '5': $symbol2 = 50; break;
					case '6': $symbol2 = 60; break;
					case '7': $symbol2 = 70; break;
					case '8': $symbol2 = 80; break;
					case '9': $symbol2 = 90; break;
				}
				imagecopy($image, $r_font, 93, 4, $symbol, 0, 10, 10);
				imagecopy($image, $r_font, 88, 4, 100, 0, 10, 10);
				imagecopy($image, $r_font, 76, 4, $symbol2, 0, 10, 10);
				$symbol_count = strlen($imdb_votes);
				for ($i = 0, $next = 105 - $symbol_count * 5; $i != $symbol_count; $i++, $next = $next + 5) {
					$symbol = substr($imdb_votes, $i, 1);
					if ($symbol == ',') $symbol = 40; else $symbol = intval($symbol) * 4;
					imagecopy($image, $v_font, $next, 18, $symbol, 0, 4, 6);
				}
				imagettftext($image, 6, 0, 4, 45, $color, $font, $imdb_sitename);
				for ($i = 0, $next = 0; $i != (int)$imdb_rating; $i++, $next = $next + 12) {
					imagecopy($image, $star, $next, 27, 0, 0, 10, 10);
				}
				$half_rating = explode('.', $imdb_rating);
				$half_rating = end($half_rating);
				imagecopy($image, $star, $next, 27, 0, 0, $half_rating, 11);
				imagepng($image, $imdb_rating_dir . $options->xfiImdbTheme . $id . '.png', 9);
				$image = imagecreatefrompng($imdb_rating_dir . $options->xfiImdbTheme . $id . '.png');
			}
		} else {
			$image = imagecreatefrompng($imdb_rating_dir . $options->xfiImdbTheme . $id . '.png');
		}

		ob_start();
		header("Content-type: image/png");
		imagepng($image);
		imagedestroy($image);

		if ($options->xfiImdbUrl) {
			return '<a href="http://www.imdb.com/title/' . $id . '" target="_blank"><img src="data:image/png;base64,' . base64_encode(ob_get_clean()) . '"></a>';
		} else {
			return '<img src="data:image/png;base64,' . base64_encode(ob_get_clean()) . '">';
		}
	}
}
