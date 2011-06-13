var markerMathStart = "<span class=\"guttermath\">&and;</span>";
var markerMathStop = "<span class=\"guttermath\">&or;</span>";
var markerMathMiddle = "<span class=\"guttermath\">|</span>";
var markerMathMinimized = "<span class=\"guttermath\">+</span>";

function setEditorTagState(editor) {
    /* setEditorTagState(editor)
     *
     * Set the internal flags of the translation editor based on the
     * current cursor location. The editor.transPreTag is set iff the
     * cursor is located immediately before an opening < bracket. The
     * editor.transPostTag is set iff the cursor is immediately after
     * a closing > bracket. The editor.transInTag is set iff the
     * cursor is between an opening < bracket and a closing > bracket.
     */

    cursor = editor.getCursor();
    text = editor.getLine(cursor.line);

    // Check if inside a tag
    if(cursor.ch > 0) {
	openBracket = text.lastIndexOf("<", cursor.ch-1);
	closeBracket = text.lastIndexOf(">", cursor.ch-1);
	editor.transInTag = (openBracket > closeBracket);
    } else
	editor.transInTag = false;

    if(!editor.somethingSelected()) {
	// No text selected: check if cursor is before, after or inside a tag.
	editor.transEditableSelection = true;

	// Check if right before a tag
	editor.transPreTag = (text.indexOf("<", cursor.ch) == cursor.ch);

	// Check if right after a tag
	editor.transPostTag = ((cursor.ch > 0) && (text.lastIndexOf(">", cursor.ch-1) == cursor.ch-1));
    } else {
	// Text selected: check whether it contains any tags
	editor.transPreTag = false;
	editor.transPostTag = false;

	selectedText = editor.getSelection();
	editor.transEditableSelection = ((selectedText.indexOf("<") == -1) && (selectedText.indexOf(">") == -1));
    }

    //document.getElementById("info").value +=  "in tag:                 " + editor.transInTag + "\n";
    //document.getElementById("info").value += "just before tag:        " + editor.transPreTag + "\n";
    //document.getElementById("info").value += "just after tag:         " + editor.transPostTag + "\n";
    //document.getElementById("info").value += "selection contains tag: " + !editor.transEditableSelection + "\n";
}

function insertSurroundingTag(editor, tag) {
    /* insertSurroundingTag(editor, tag)
     *
     * Insert an opening and closing tag pair with the name given by
     * the tag argument. Any selected text will be placed *inbetween*
     * the opening and closing tags. This function respects the
     * readOnly state of the editor.
     */
    if(!editor.getOption("readOnly")) {
	selectedText = editor.getSelection();
	editor.replaceSelection("<" + tag + ">" + selectedText + "</" + tag + ">");
    } else {
	// Check if it is a tag that should be removed
	selectedText = editor.getSelection();
	var openTagStop = tag.length + 2;
	var closeTagStart = selectedText.length - (tag.length+3);
	if((selectedText.substring(0, openTagStop) == "<" + tag + ">") &&
	   (selectedText.substring(closeTagStart) == "</" + tag + ">")) {
	    editor.replaceSelection(selectedText.substring(openTagStop, closeTagStart));
	}
    }
    editor.focus(); // Return focus from the clicked button to the editor
}

function showHideMathml(editor, lineNumber) {
    /* showHideMathml(editor)
     *
     * If the cursor is inside a <m:math></m:math> tag, reduce it to a
     * <m:math id="..."/> tag. If the cursor is inside a
     * <m:math id="..."/> tag, restore it to its original state.
     */
    
    if((editor.lineInfo(lineNumber).markerText == markerMathStart) ||
       (editor.lineInfo(lineNumber).markerText == markerMathMiddle) ||
       (editor.lineInfo(lineNumber).markerText == markerMathStop)) {
	// Minimize a math block

	// Find the first and last line numbers of the block
	mathFirstLine = lineNumber;
	while(editor.lineInfo(mathFirstLine).markerText != markerMathStart)
	    mathFirstLine -= 1;
	mathLastLine = lineNumber;
	while(editor.lineInfo(mathLastLine).markerText != markerMathStop)
	    mathLastLine += 1;

	// Get the spaces before the tag (the line indent)
	firstLine = editor.getLine(mathFirstLine);
	lineIndent = firstLine.substring(0, firstLine.indexOf("<"));

	// Store the current text and replace it with the minimized text
	editor.storedMaths[editor.storedMathsCounter] =
	    editor.getRange({line: mathFirstLine, ch: 0},
			    {line: mathLastLine+1, ch: 0});
	editor.replaceRange(lineIndent + "<m:math restore=\"" + editor.storedMathsCounter + "\">\n",
			    {line: mathFirstLine, ch: 0},
			    {line: mathLastLine+1, ch: 0});
	editor.storedMathsCounter += 1;
	editor.storedMathsEntries += 1;

	// Set the gutter marker
	editor.setMarker(mathFirstLine, markerMathMinimized);

	editor.setCursor({line: mathFirstLine, ch: 0});
	
    } else if(editor.lineInfo(lineNumber).markerText == markerMathMinimized) {
	// Restore a math block

	// Get the storage id
	line = editor.getLine(lineNumber);
	storageId = parseInt(line.substring(line.indexOf('"')+1, line.lastIndexOf('"')));

	// Replace the minimized text with the stored text
	editor.replaceRange(editor.storedMaths[storageId],
			    {line: lineNumber, ch: 0},
			    {line: lineNumber+1, ch: 0});

	// Set the gutter markers
	lineCount = editor.storedMaths[storageId].split('\n').length - 1;
	editor.setMarker(lineNumber, markerMathStart);
	for(i = 1; i < lineCount-1; i++)
	    editor.setMarker(lineNumber+i, markerMathMiddle);
	editor.setMarker(lineNumber+lineCount-1, markerMathStop);

	// Free some memory
	delete editor.storedMaths[storageId];
	editor.storedMathsEntries -= 1;
	if(editor.storedMathsEntries == 0)
	    editor.storedMathsCounter = 0;

	editor.setCursor({line: lineNumber, ch: 0});

    }
    document.getElementById("info").value = editor.storedMathsEntries + " " + editor.storedMathsCounter;
    /*
    if(editor.getline(lineNumber).trim() == "<m:math restore=\"" ... "\"/>") {
	// Restore a line
	editor.storedMaths[id] = editor.text[mathStart:mathStop];
	editor.replace(lineNumber, editor.storedMaths[id]);
	del editor.storedMaths[id];
    }
    */
}

var editor = CodeMirror.fromTextArea(
    document.getElementById("code"), {
	mode: "application/xml",
	gutter: true,
	onCursorActivity: function(editor) {
	    setEditorTagState(editor);
            editor.setOption("readOnly", editor.transInTag || !editor.transEditableSelection);
	},
	onChange: function(editor) {
	    if(editor) { // Need to guard against first call when editor does not yet exist
		setEditorTagState(editor);
		editor.setOption("readOnly", editor.transInTag || !editor.transEditableSelection);
	    }
	},
	onKeyEvent: function(editor, key) {
	    var keyCode = key.keyCode || key.which;
	    //document.getElementById("info").value += key.type + " " + keyCode + "\n";
	    deletePressed = false;
	    backspacePressed = false;
	    leftAnglePressed = false;
	    rightAnglePressed = false;
	    if(key.type == "keydown")
		editor.transKeyDown = keyCode;
	    else if(key.type == "keypress") {
		if((editor.transKeyDown == 46) && (keyCode == 46))
		    deletePressed = true;
		if((editor.transKeyDown == 8) && (keyCode == 8))
		    backspacePressed = true;
		if((editor.transKeyDown == 188) && (keyCode == 60))
		    leftAnglePressed = true;
		if((editor.transKeyDown == 190) && (keyCode == 62))
		    rightAnglePressed = true;
	    }
	    if((deletePressed && editor.transPreTag) ||
	       (backspacePressed && editor.transPostTag) ||
	       leftAnglePressed || rightAnglePressed) {
		key.stop();
	    }
	},
	onGutterClick: function(editor, line) {
	    showHideMathml(editor, line);
	},
    }
);

// Do initial formatting of editor gutter
editor.storedMaths = {};
editor.storedMathsCounter = 0;
editor.storedMathsEntries = 0;
active = false;
for(line = 0; line < editor.lineCount(); line++) {
    text = editor.getLine(line).replace(/^\s+|\s+$/g, ''); // trim white space
    if(text.substring(0, 7) == "<m:math") {
	editor.setMarker(line, markerMathStart);
	active = true;
    } else if(text.substring(0, 9) == "</m:math>") {
	editor.setMarker(line, markerMathStop);
	active = false;
    } else if(active)
	editor.setMarker(line, markerMathMiddle);
}
