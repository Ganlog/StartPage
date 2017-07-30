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
	generateID: function(){
		var date = new Date();
		var year = String(date.getFullYear()).substring(2, 4);
		var month = date.getMonth()
			month = (month+1 > 9) ? month+1 : "0"+(month+1); // January is 0
		var day = date.getDate();
			day = (day > 9) ? day : "0"+day;
		var hours = date.getHours();
			hours = (hours > 9) ? hours : "0"+hours;
		var minutes = date.getMinutes();
			minutes = (minutes > 9) ? minutes : "0"+minutes;
		var seconds = date.getSeconds();
			seconds = (seconds > 9) ? seconds : "0"+seconds;
		var miliseconds = date.getMilliseconds();
			miliseconds = (miliseconds > 99) ? miliseconds : (miliseconds > 9) ? "0"+miliseconds : "00"+miliseconds;

		var newID = year+''+month+''+day+''+hours+''+minutes+''+seconds+''+miliseconds;
		while(document.getElementById(newID))	// while element with this ID exists, increase it by 1
			newID = parseInt(newID)+1
		return newID;
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
