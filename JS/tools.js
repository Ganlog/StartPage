tools = {
	changeCSS: function(selector, property, value){
		var i, j, found = false, allStyleSheets = document.styleSheets, styleSheet;
		for (i = 0; i < allStyleSheets.length; i++){ //Loop through all <style>
			if(allStyleSheets[i].cssRules)
				for (j = 0; j < allStyleSheets[i].cssRules.length; j++){ // Loop through all properties ('p', 'div', '.', '#', etc.)
					var actSelector = allStyleSheets[i].cssRules[j].selectorText;
					if(actSelector == selector){
						found = true;
						break;
					}
				}
			if(found) break;
		}
		if(found)
			allStyleSheets[i].cssRules[j].style[property] = value;
		else{
			if(allStyleSheets[0])	// if any stylesheet exist insert rule to it
				styleSheet = allStyleSheets[0];
			else{	// otherwise create new stylesheet
				var newStyle = document.createElement("style");
				document.head.appendChild(newStyle);
				styleSheet = newStyle.sheet;
			}
			styleSheet.insertRule(selector+" {"+property+": "+value+"}",0);
		}
	},

	addKeyPressedListener: function(key, task){
		this.keyboard.keyFunction["k"+key] = task;
	},
	removeKeyPressedListener: function(key){
		delete this.keyboard.keyFunction["k"+key];
	},
	keyboard:{
		keyPressed: [],
		keyFunction: [],
		keyStateListener: (function(){
			onkeydown = function(e){
				if(e.target.type != 'text'){
					if(!tools.keyboard.keyPressed["k"+e.keyCode]){
						tools.keyboard.keyPressed["k"+e.keyCode] = true;
						// execute function assigned to key
						if(tools.keyboard.keyFunction["k"+e.keyCode])
							tools.keyboard.keyFunction["k"+e.keyCode]();
					}
				}
			}
			onkeyup = function(e){
				if(e.target.type != 'text'){
					delete tools.keyboard.keyPressed["k"+e.keyCode];
				}
			}
		})(),
	},
}




Object.defineProperty(Element.prototype, 'outerHeight', {
	'get': function(){
		var height = this.clientHeight;
		var computedStyle = window.getComputedStyle(this);
		height += parseInt(computedStyle.marginTop);
		height += parseInt(computedStyle.marginBottom);
		height += parseInt(computedStyle.borderTopWidth);
		height += parseInt(computedStyle.borderBottomWidth);
		return height;
	}
});

Object.defineProperty(Element.prototype, 'outerWidth', {
	'get': function(){
		var width = this.clientWidth;
		var computedStyle = window.getComputedStyle(this);
		width += parseInt(computedStyle.marginLeft);
		width += parseInt(computedStyle.marginRight);
		width += parseInt(computedStyle.borderLeftWidth);
		width += parseInt(computedStyle.borderRightWidth);
		return width;
	}
});
