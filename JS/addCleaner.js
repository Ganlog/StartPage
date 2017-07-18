var usifrme = document.getElementsByTagName("iframe");
var usifrmeln = usifrme.length;
var usscrpt = document.body.getElementsByTagName("script");
var usscrptln = usscrpt.length;
var usobrz = document.body.getElementsByTagName("img");
var usobrzln = usobrz.length;
for(i=0;i<usifrmeln;i++){usifrme[0].remove();}
for(i=0;i<usscrptln;i++){usscrpt[0].remove();}
for(i=0;i<usobrzln;i++){
	if(usobrz[i].src=="http://static.friko.pl/img/close.gif") usobrz[i].remove();
}