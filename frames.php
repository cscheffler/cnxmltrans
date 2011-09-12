<?php include("translate.php"); ?>
<html>
  <head>
    <title>CNXML Side-by-Side Editor</title>
<?php

$module = $_GET["getModule"];
if($module) {
  $sourceLanguage = $_GET["sourceLanguage"];
  $targetLanguage = $_GET["targetLanguage"];
?>
  </head>
  <frameset cols="*,*" frameborder=1 framespacing=0 rows="*">
    <frame src="index.php?getModule=<?php echo $module; ?>&getLanguage=<?php echo $sourceLanguage; ?>" noresize
	   scrolling=no marginwidth=0 marginheight=0>
    <frame src="index.php?getModule=<?php echo $module; ?>&getLanguage=<?php echo $targetLanguage; ?>" noresize
	   scrolling=no marginwidth=0 marginheight=0>
  </frameset>
<?php
} else {
?>
    <link rel="stylesheet" href="lib/codemirror.css">
    <link rel="stylesheet" href="xml.css">
    <style type="text/css">.CodeMirror {border-top: 1px solid black; border-bottom: 1px solid black;}</style>
    <link rel="stylesheet" href="css/docs.css">
  </head>
  <body>
    <h2>CNXML Side-by-Side Editor</h2>
    <form method="get" action="frames.php">
      <p>Module number: <input type="text" name="getModule" value="<?php echo $module; ?>" size="10"/>
<?php

for($i = 0; $i < 2; $i++) {
  if($i == 0) {
    $prompt = "Source language";
    $selectName = "sourceLanguage";
    $default = "en";
  } else {
    $prompt = "Target language";
    $selectName = "targetLanguage";
    $default = "en";
  }
  echo '      &nbsp;&nbsp; ' . $prompt . ': <select name="' . $selectName . '">' . "\n";

  foreach($transLanguageList as $key=>$entry) {
    $entry = explode(":", $entry);
    echo '          <option value="' . $entry[0] . '"';
    if($entry[0] == $default)
      echo ' selected';
    echo '>' . $entry[1] . '</option>' . "\n";
  }

  echo "        </select>\n";
}

?>
        &nbsp;&nbsp; <input type="submit" value="Translate"/>
      </p>
    </form>
  </body>
<?php
}
?>
</html>
