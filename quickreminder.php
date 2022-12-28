<html>
<head>
<script type='text/javascript'>
// https://whatwebcando.today/geolocation.html
var watchId;

function appendLocation(location) {
	
	const req = new XMLHttpRequest();
	req.onload = onLoad;

	req.responseType = "json";


	req.open('POST', 'ajax_quickreminder.php', true);
	req.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
	req.send('loc='+ location.coords.latitude + ',' + location.coords.longitude);
  
   
}

function onLoad(event) {
 
    if (this.status === 200) {
        //document.write(this.response.id + " "+ this.response.distance);
		window.location.href="reminder.php?id="+this.response.id;
    } else {
        document.write("Status de la réponse: %d (%s)", this.status, this.statusText);
    }
}

function checkpos()
{

	if ('geolocation' in navigator) {
  
    navigator.geolocation.getCurrentPosition(function (location) {
      appendLocation(location);
  });
} else {
  alert('Geolocation API not supported.');
}
}


</script>
</head>
<body onload="Javascript:checkpos();">

</body>