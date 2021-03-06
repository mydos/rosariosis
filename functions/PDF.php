<?php

//FJ wkhtmltopdf
//@param $css = true
//include theme CSS in HTML output
//@param $margins = array('top'=> 1, 'bottom'=> 1, 'left'=> 1, 'right'=> 1)
//margins unit in millimeters
//Note: for landscape format, set $_SESSION['orientation'] = 'landscape'
function PDFStart($css = true, $margins = array())
{
	$_REQUEST['_ROSARIO_PDF'] = true;
	$pdfitems['css'] = $css;
	$pdfitems['margins'] = $margins;
	ob_start();
	return $pdfitems;
}

function PDFStop($handle)
{
	global $wkhtmltopdfPath,$wkhtmltopdfAssetsPath,$RosarioPath,$locale;
	
	$handle['orientation'] = $_SESSION['orientation'];
	unset($_SESSION['orientation']);

	$html_content = ob_get_clean();
	
	//convert to HTML page with CSS		
	$RTL_languages = array('ar', 'he', 'dv', 'fa', 'ur');
	$html = '<!DOCTYPE html><HTML lang="'.mb_substr($locale,0,2).'" '.(in_array(mb_substr($locale,0,2), $RTL_languages)?' dir="RTL"':'').'><HEAD><meta charset="UTF-8" />';
	if ($handle['css'])
		$html .= '<link rel="stylesheet" type="text/css" href="assets/themes/'.Preferences('THEME').'/stylesheet_wkhtmltopdf.css" />';
	//FJ bugfix wkhtmltopdf screen resolution on linux
	//see: https://code.google.com/p/wkhtmltopdf/issues/detail?id=118
	$html .= '<TITLE>'.str_replace(_('Print').' ','',ProgramTitle()).'</TITLE></HEAD><BODY><div style="width:'.((!empty($handle['orientation']) && $handle['orientation'] == 'landscape') ? '1448' : '1024').'px" id="pdf">'.$html_content.'</div></BODY></HTML>';

	//FJ wkhtmltopdf
	if (!empty($wkhtmltopdfPath))
	{		
		// You can override the Path definition in the config.inc.php file
		if (!isset($wkhtmltopdfAssetsPath))
			$wkhtmltopdfAssetsPath = $RosarioPath.'assets/'; // way wkhtmltopdf accesses the assets/ directory, empty string means no translation

		if(!empty($wkhtmltopdfAssetsPath))
			$html = str_replace('assets/', $wkhtmltopdfAssetsPath, $html);
			
		$html = str_replace('modules/', $RosarioPath.'modules/', $html);
		
		require('classes/Wkhtmltopdf.php');
		
		try {
			//indicate to create PDF in the temporary files system directory
			$wkhtmltopdf = new Wkhtmltopdf(array('path' => sys_get_temp_dir()));
			
			$wkhtmltopdf->setBinPath($wkhtmltopdfPath);
			
			if (Preferences('PAGE_SIZE') != 'A4')
				$wkhtmltopdf->setPageSize(Preferences('PAGE_SIZE'));
				
			if (!empty($handle['orientation']) && $handle['orientation'] == 'landscape')
				$wkhtmltopdf->setOrientation(Wkhtmltopdf::ORIENTATION_LANDSCAPE);
			
			if (!empty($handle['margins']) && is_array($handle['margins']))
				$wkhtmltopdf->setMargins($handle['margins']);
			
			$wkhtmltopdf->setTitle(utf8_decode(str_replace(_('Print').' ','',ProgramTitle())));
			
			//directly pass HTML code
			$wkhtmltopdf->setHtml($html);
			
			//MODE_EMBEDDED displays PDF in browser, MODE_DOWNLOAD forces PDF download
			//FJ force PDF DOWNLOAD for Android mobile & tablet
			if (mb_stripos($_SERVER['HTTP_USER_AGENT'],'android') !== false)
				$wkhtmltopdf->output(Wkhtmltopdf::MODE_DOWNLOAD, str_replace(array(_('Print').' ', ' '),array('', '_'),utf8_decode(ProgramTitle())).'.PDF');
			else
				$wkhtmltopdf->output(Wkhtmltopdf::MODE_EMBEDDED, str_replace(array(_('Print').' ', ' '),array('', '_'),utf8_decode(ProgramTitle())).'.pdf');
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}
	else
		echo $html;
}
?>
