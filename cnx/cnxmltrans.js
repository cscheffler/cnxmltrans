function setTagState(editor) {
    cursor = editor.getCursor();
    text = editor.getLine(cursor.line);

    // Check if right before a tag
    editor.transPreTag = (text.indexOf("<", cursor.ch) == cursor.ch);

    // Check if right after a tag
    editor.transPostTag = ((cursor.ch > 0) && (text.lastIndexOf(">", cursor.ch-1) == cursor.ch-1));

    // Check if inside a tag
    if(cursor.ch > 0) {
	openBracket = text.lastIndexOf("<", cursor.ch-1);
	closeBracket = text.lastIndexOf(">", cursor.ch-1);
	editor.transInTag = (openBracket > closeBracket);
    } else
	editor.transInTag = false;
}

var editor = CodeMirror.fromTextArea(
    document.getElementById("code"), {
	mode: "application/xml",
	onCursorActivity: function(editor) {
	    setTagState(editor);
            editor.setOption("readOnly", editor.transInTag);
	},
	onChange: function(editor) {
	    if(editor) { // Need to guard against first call when editor does not yet exist
		setTagState(editor);
		editor.setOption("readOnly", editor.transInTag);
	    }
	},
	onKeyEvent: function(editor, key) {
	    var keyCode = key.keyCode || key.which;
	    //document.getElementById("info").value += " " + key.type
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
