Element.prototype.smoothlyScrollBy = window.smoothlyScrollBy = function(moveBy){
	var self = this;
	if(moveBy != 0){
		var alreadyMovedBy = 0;
		var move = function(){
			var step = moveBy/30;
			step *= (step<1 && step>-1) ? 10 : 1;
			self.scrollBy(0, -step);
			alreadyMovedBy += step;
			if(Math.abs(alreadyMovedBy) < Math.abs(moveBy))
				setTimeout(move, 1);
		};
		setTimeout(move, 1);
	}
}

window.addEventListener('mousewheel', mouseWheelEvent); // Chrome
window.addEventListener('DOMMouseScroll', mouseWheelEvent); // Firefox

function mouseWheelEvent(e) {
	var delta = e.wheelDelta
	if(e.detail) delta = (e.detail > 0) ? -120 : 120; // for firefox

	if(e.target.id == "folders" || e.target.id == "mainFolders" ||
	(e.target.parentNode) && e.target.parentNode.id == "mainFolders"){
		document.getElementById("mainFolders").smoothlyScrollBy(delta);
	}

	else if(e.target.id == "window" ||
	(e.target.parentNode)  && e.target.parentNode.id == "window" ||
	(e.target.parentNode.parentNode) && e.target.parentNode.parentNode.id == "window" ||
	(e.target.parentNode.parentNode.parentNode) && e.target.parentNode.parentNode.parentNode.id == "window"){
		document.getElementById("window").smoothlyScrollBy(delta);
	}

	else{
		window.smoothlyScrollBy(delta);
	}
}
