<?php

class Html {

	static function explorerLink($explorerContentUrl, $extraJs=null) {
		$fileString = basename(parse_url($explorerContentUrl, PHP_URL_PATH), '.php');
		$parameterString = parse_url($explorerContentUrl, PHP_URL_QUERY);
		return "href='index.php?view=".urlencode($fileString)."&".$parameterString."'"
			.($extraJs===null ? "" : " onclick='event.preventDefault();".$extraJs."'");
	}

	static function progressBar($percent, $cid=null, $tid=null, $class=''/*hidden big stretch animated*/, $style='', $text=null) {
		$percent = intval($percent);
		return
			'<span class="progressbar-container '.$class.'" style="--progress:'.$percent.'%; '.$style.'" '.($cid==null ? '' : 'id="'.htmlspecialchars($cid).'"').'>'
				.'<span class="progressbar"><span class="progress"></span></span>'
				.'<span class="progresstext" '.($tid==null ? '' : 'id="'.htmlspecialchars($tid).'"').'>'.(
					$text ? htmlspecialchars($text) : (strpos($class,'animated')!==false ? LANG('in_progress') : $percent.'%')
				).'</span>'
			.'</span>';
	}

	static function dictTable($value, array $exclude=[]) {
		if($value === true) echo '<img title="'.LANG('yes').'" src="img/success.dyn.svg">';
		elseif($value === false) echo '<img title="'.LANG('no').'" src="img/close.opacity.svg">';
		elseif(is_array($value)) {
			echo '<table class="list metadata"><tbody>';
			foreach($value as $subkey => $subvalue) {
				if(in_array($subkey, $exclude)) continue;
				echo '<tr>'
					.'<th>'.htmlspecialchars(LANG($subkey)).'</th>'
					.'<td>';
				self::dictTable($subvalue);
				echo '</td>'
					.'</tr>';
			}
			echo '</tbody></table>';
		}
		else echo htmlspecialchars($value);
	}

}
