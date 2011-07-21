<?php

$DEBUG = false;

$url = $_GET["getUrl"];
if($url) {
  if(substr($url, -5) == "/view")
    $url = substr($url, 0, -4) . "index.cnxml";
  elseif(substr($url, -1) == "/")
    $url = $url . "index.cnxml";

  if($DEBUG)
    $fp = fopen("index.cnxml", "rb");
  else
    $fp = fopen($url, "rb");
  $text = "";
  while(!feof($fp)) {
    $text .= fread($fp, 8192);
  }
  fclose($fp);
} else {
  $url = "";
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
      <input type="text" name="getUrl" value="<?php echo $url; ?>" size="50"/>
      <input type="submit" value="Get"/>
    </form>
    <form>
      <input type="hidden" name="metadata" value="<?php echo htmlentities($metaText); ?>"/>
      <input type="button" value="emphasis" onClick="insertSurroundingTag(editor, 'emphasis');"/>
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
