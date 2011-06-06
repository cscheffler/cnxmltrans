function setTagState(editor) {
    /* setTagState(editor)
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

	/*
	 * This code, which adds lines from the editor when an XML tag
	 * spans multiple lines, should not be necessary anymore since
	 * the PHP code in index.php forces XML tags to be on one
	 * line.

	firstLineNumber = cursor.line;
	lastLineNumber = cursor.line;
	text = editor.getLine(cursor.line);

	while(true) {
	    firstOpen = text.indexOf("<");
	    firstClose = text.indexOf(">");
	    if(((firstOpen == -1) && (firstClose != -1)) || (firstOpen > firstClose)) {
		if(firstLineNumber == 0)
		    break;
		firstLineNumber -= 1;
		text = editor.getLine(firstLineNumber) + text;
		continue;
	    }

	    lastOpen = text.lastIndexOf("<");
	    lastClose = text.lastIndexOf(">");
	    if(((lastOpen != -1) && (lastClose == -1)) || (lastOpen > lastClose)) {
		if(lastLineNumber == editor.lineCount()-1)
		    break;
		lastLineNumber += 1;
		text = text + editor.getLine(lastLineNumber);
		continue;
	    }

	    break;
	}
	document.getElementById("info").value = "(" + firstLineNumber + ":" + lastLineNumber + "), (" + text + ")";
	*/

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

    document.getElementById("info").value =  "in tag:                 " + editor.transInTag + "\n";
    document.getElementById("info").value += "just before tag:        " + editor.transPreTag + "\n";
    document.getElementById("info").value += "just after tag:         " + editor.transPostTag + "\n";
    document.getElementById("info").value += "selection contains tag: " + !editor.transEditableSelection + "\n";
}

var editor = CodeMirror.fromTextArea(
    document.getElementById("code"), {
	mode: "application/xml",
	onCursorActivity: function(editor) {
	    setTagState(editor);
            editor.setOption("readOnly", editor.transInTag || !editor.transEditableSelection);
	},
	onChange: function(editor) {
	    if(editor) { // Need to guard against first call when editor does not yet exist
		setTagState(editor);
		editor.setOption("readOnly", editor.transInTag || !editor.transEditableSelection);
	    }
	},
	onKeyEvent: function(editor, key) {
	    var keyCode = key.keyCode || key.which;
	    //document.getElementById("info").value += " " + key.type;
	    //document.getElementById("info").value += " " + keyCode;
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
	}
    }
);
