<?php

include("translate.php");

$DEBUG = false;

$module = $_GET["getModule"];
//$module = 'm11429';
if($module) {
  if($DEBUG) {
    $fp = fopen("index.cnxml", "rb");
    $language = "en"; // Engish implies no translation
  } else {
    $url = 'http://cnx.org/content/' . $module . '/latest/index.cnxml';
    $fp = fopen($url, "rb");
    $language = $_GET["getLanguage"];
  }

  // Get original CNXML
  $text = "";
  while(!feof($fp)) {
    $text .= fread($fp, 8192);
  }
  fclose($fp);

  // Translate
  if($language != "en") {
    $version = get_cnxml_version($text);
    $url = 'http://cnx.org/content/' . $module . '/' . $version . '/module_export?format=plain';
    $translation = translate_cnxml($url, "en", $language);
    $text = fix_cnxml_translation($text, $translation);
  }
} else {
  $module = "";
  $language = "en";
  $text = "";
}

// Clean text
// Remove metadata
$metaStart = strpos($text, "<metadata ");
if($metaStart !== FALSE) {
  $metaStop = strpos($text, "</metadata>") + 11;
  $metaText = substr($text, $metaStart, $metaStop-$metaStart);
  $text = substr($text, 0, $metaStart) . "<metadata/>" . substr($text, $metaStop);
} else
  $metaText = "";

// Force XML tags to be on 1 line
$closePos = -1;
$oldText = $text;
$text = "";
while(TRUE) {
  $startPos = $closePos+1;
  $openPos = strpos($oldText, "<", $startPos);
  if($openPos === FALSE)
    break;
  $closePos = strpos($oldText, ">", $openPos+1);
  if($closePos === FALSE)
    break;
  $text .= substr($oldText, $startPos, $openPos-$startPos);
  $text .= preg_replace('!\s+!', ' ', substr($oldText, $openPos, $closePos-$openPos+1));
}
$text .= substr($oldText, $startPos);

// Clean up XML tag indentation and text wrapping
$tagsNoNewLine = array("emphasis");
$indent = 0;
$tagStack = array();
$textPos = 0;
$newText = "";
$maxColumns = 160;

function wrap_text($text, $indent, $columns) {
  $indent = str_repeat(" ", $indent);
  return $indent . wordwrap(preg_replace('!\s+!', ' ', $text), $columns, "\n" . $indent);
}

while(TRUE) {
  $tagStart = strpos($text, "<", $textPos);
  if($tagStart === FALSE)
    break;
  $tagStop = strpos($text, ">", $tagStart);
  $preTag = trim(substr($text, $textPos, $tagStart-$textPos)); // Everything before the next tag
  $tag = substr($text, $tagStart, $tagStop-$tagStart+1);
  $textPos = $tagStop + 1;

  if(strlen($preTag) > 0)
    $newText .= wrap_text($preTag, $indent, $maxColumns) . "\n";

  if($tag[1] == "/") {
    // Closing tag
    $indent -= 1;
    $newText .= str_repeat(" ", $indent) . $tag . "\n";
  } else {
    // Opening or self-closing tag or comment
    $newText .= str_repeat(" ", $indent) . $tag . "\n";
    if(($tag[strlen($tag)-2] != '/') and
       (substr($tag, 0, 4) != "<!--"))
      $indent += 1;
  }
}

$text = $newText;

?>
<html>
  <head>
    <title>CNXML Editor</title>
    <link rel="stylesheet" href="lib/codemirror.css">
    <script src="lib/codemirror.js"></script>
    <script src="xml.js"></script>
    <link rel="stylesheet" href="xml.css">
    <style type="text/css">.CodeMirror {border-top: 1px solid black; border-bottom: 1px solid black;}</style>
    <link rel="stylesheet" href="css/docs.css">
  </head>
  <body>
    <h2>CNXML Editor</h2>
    <form method="get" action="index.php">
      <p>Module number: <input type="text" name="getModule" value="<?php echo $module; ?>" size="10"/>
      &nbsp;&nbsp; Target language: <select name="getLanguage">
<?php

foreach($transLanguageList as $key=>$entry) {
  $entry = explode(":", $entry);
  echo '          <option value="' . $entry[0] . '"';
  if($entry[0] == $language)
    echo ' selected';
  echo '>' . $entry[1] . '</option>' . "\n";
}

?>        </select>
        &nbsp;&nbsp; <input type="submit" value="Get"/>
      </p>
    </form>
    <form>
      <input type="hidden" name="metadata" value="<?php echo htmlentities($metaText); ?>"/>
      <input type="button" value="emphasis tag" onClick="insertSurroundingTag(editor, 'emphasis');"/>
      <br/>
      <textarea id="code" name="code" onClick="alert(\"blah\");"><?php echo htmlentities($text); ?></textarea>
      <br/>
<?php if($DEBUG) { ?>
      <h2>Debug Info</h2>
      <textarea id="info" name="info" rows="10" cols="80"></textarea>
      <br><input type="text" id="keycode" size="15" />
<?php } ?>
    </form>
    <script type="text/javascript" src="cnxmltrans.js">
    </script>
  </body>
</html>