<?php

$TEST = FALSE;
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_BAIL, 1);
assert_options(ASSERT_BAIL, 1);

if($TEST) {
  $module = 'm11429';
  $sl = 'en';
  $tl = 'af';
}

$transLanguageList = array(
  "af:Afrikaans",
  "sq:Albanian",
  "ar:Arabic",
  "be:Belarusian",
  "bg:Bulgarian",
  "ca:Catalan",
  "zh:Chinese",
  "hr:Croatian",
  "cs:Czech",
  "da:Danish",
  "nl:Dutch",
  "en:English",
  "et:Estonian",
  "tl:Filipino",
  "fi:Finnish",
  "fr:French",
  "gl:Galician",
  "de:German",
  "el:Greek",
  "he:Hebrew",
  "hi:Hindi",
  "hu:Hungarian",
  "is:Icelandic",
  "id:Indonesian",
  "ga:Irish",
  "it:Italian",
  "ja:Japanese",
  "ko:Korean",
  "lv:Latvian",
  "lt:Lithuanian",
  "mk:Macedonian",
  "ms:Malay",
  "mt:Maltese",
  "no:Norwegian",
  "fa:Persian",
  "pl:Polish",
  "pt:Portuguese",
  "ro:Romanian",
  "ru:Russian",
  "sr:Serbian",
  "sk:Slovak",
  "sl:Slovenian",
  "es:Spanish",
  "sw:Swahili",
  "sv:Swedish",
  "th:Thai",
  "tr:Turkish",
  "uk:Ukrainian",
  "vi:Vietnamese",
  "cy:Welsh",
  "yi:Yiddish",
);

function module_to_cnxml($iModule) {
  // Download CNXML based on module number
  $url = 'http://cnx.org/content/' . $iModule . '/latest/index.cnxml';
  $fp = fopen($url, "rb");
  $cnxml = "";
  while(!feof($fp)) {
    $cnxml .= fread($fp, 8192);
  }
  fclose($fp);
  return $cnxml;
}

function get_cnxml_version($iCnxml) {
  // Parse out module version number
  $pos = stripos($iCnxml, '<md:version>') + 12;
  $version = str_replace(',', '.', trim(substr($iCnxml, $pos, stripos($iCnxml, '</md:version>')-$pos)));
  return $version;
}

function translate_cnxml($iUrl, $iSourceLang, $iTargetLang) {
  // Get Google translated CNXML. This may mangle mathematics.
  $transUrl = 'http://translate.google.com/translate_c?hl=en&ie=UTF8&prev=_t&rurl=translate.google.com&sl=' . $iSourceLang . '&tl=' . $iTargetLang . '&twu=1&u=' . rawurlencode($iUrl);

  $command = 'wget -q -O - --user-agent="Lynx/2.8.8dev.3 libwww-FM/2.14 SSL-MM/1.4.1" "' . $transUrl . '"';
  exec($command, $output);
  $output = implode("\n", $output);

  $substr = '<a href="';
  $pos0 = stripos($output, $substr) + strlen($substr);
  $substr = '">Translate</a>';
  $pos1 = stripos($output, $substr);
  $transUrl = substr($output, $pos0, $pos1-$pos0);

  $transUrl = 'http://translate.google.com' . str_replace(array('&amp;'), array('&'), $transUrl);

  $command = 'wget -q -O - --user-agent="Lynx/2.8.8dev.3 libwww-FM/2.14 SSL-MM/1.4.1" "' . $transUrl . '"';
  exec($command, $output1);
  $output = implode("\n", $output1);

  $substr = 'content="0;URL=';
  $pos0 = stripos($output, $substr) + strlen($substr);
  $substr = '"></';
  $pos1 = stripos($output, $substr, $pos0);
  $transUrl = substr($output, $pos0, $pos1-$pos0);
  $transUrl = str_replace(array('&amp;'), array('&'), $transUrl);

  $command = 'wget -q -O - --user-agent="Lynx/2.8.8dev.3 libwww-FM/2.14 SSL-MM/1.4.1" "' . $transUrl . '"';

  exec($command, $output);
  $output = implode("\n", $output);

  return $output;
}

function fix_cnxml_translation($iOriginal, $iTranslation) {
  // Replace "translated" maths with original maths
  $tagNames = array('m:math', 'md:email', 'md:surname', 'md:firstname',
		    'md:maintainerlist', 'md:revised', 'md:created',
		    'md:othername', 'md:version', 'md:authorlist');

  // Process start of document
  $posOrig = stripos($iOriginal, '<document') + 1;
  $posTran = stripos($iTranslation, '<document') + 1;
  $fixedCnxml = substr($iOriginal, 0, $posOrig);

  $startOrig = $posOrig;
  $startTran = $posTran;
  while(TRUE) {
    // Find the start of a tag
    $startOrig = stripos($iOriginal, '<', $startOrig);
    if($startOrig === FALSE)
      break;
    $startTran = stripos($iTranslation, '<', $startTran);

    // Determine the tag name and whether it is self-closing in the original text
    $posClosing = stripos($iOriginal, '>', $startOrig);
    $isSelfClosing = $iOriginal[$posClosing-1] == '/';
    $posTagNameEnd = stripos($iOriginal, ' ', $startOrig);
    if(($posTagNameEnd === FALSE) or ($posClosing < $posTagNameEnd)) {
      if($isSelfClosing)
	$posTagNameEnd = $posClosing-1;
      else
	$posTagNameEnd = $posClosing;
    }
    $tagName = strtolower(substr($iOriginal, $startOrig+1, $posTagNameEnd-($startOrig+1)));

    if($isSelfClosing) {
      // Google Translate seems to make open-close tag pairs out of
      // self-closing tags. Check if this happened and keep the
      // original version in all cases.
      $endOrig = $posClosing+1;
      $endTran = stripos($iTranslation, '>', $startTran);
      assert('$endTran !== FALSE');
      if($iTranslation[$endTran-1] == '/') {
	// Turns out that this is self-closing in the translation
	$endTran += 1;
      } else {
	// Was turned into an open-close tag pair: find the closing tag
	$closingTag = '</' . $tagName . '>';
	$endTran = stripos($iTranslation, $closingTag, $endTran);
	assert('$endTran !== FALSE');
	$endTran += strlen($closingTag);
      }
      $fixedCnxml .= substr($iTranslation, $posTran, $startTran-$posTran);
      $fixedCnxml .= substr($iOriginal, $startOrig, $endOrig-$startOrig);
      $posOrig = $endOrig;
      $posTran = $endTran;
      $startOrig = $posOrig;
      $startTran = $posTran;
    } elseif(in_array($tagName, $tagNames)) {
      // This tag should be left alone by Google Translate, so
      // explicitly copy the original back in.
      $closeTag = '</' . $tagName . '>';
      $endOrig = stripos($iOriginal, $closeTag, $startOrig) + strlen($closeTag);
      $endTran = stripos($iTranslation, $closeTag, $startTran) + strlen($closeTag);
      $fixedCnxml .= substr($iTranslation, $posTran, $startTran-$posTran);
      $fixedCnxml .= substr($iOriginal, $startOrig, $endOrig-$startOrig);
      $posOrig = $endOrig;
      $posTran = $endTran;
      $startOrig = $posOrig;
      $startTran = $posTran;
    } else {
      $startOrig += 1;
      $startTran += 1;
    }
  }
  $fixedCnxml .= substr($iTranslation, $posTran);
  return $fixedCnxml;
}

if($TEST) {
  $originalCnxml = module_to_cnxml($module);
  $version = get_cnxml_version($originalCnxml);
  $url = 'http://cnx.org/content/' . $module . '/' . $version . '/module_export?format=plain';
  $translatedCnxml = translate_cnxml($url, $sl, $tl);
  $fixedCnxml = fix_cnxml_translation($originalCnxml, $translatedCnxml);
  echo $fixedCnxml;
}
