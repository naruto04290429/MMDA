
document.addEventListener('DOMContentLoaded', function() {
	chrome.tabs.getSelected(null, function(tab) {
		console.log("Try to send the current url.");
		var url = new URL(tab.url);
  		console.log(""+url);
  		
  		if (window.XMLHttpRequest) {
		// code for IE7+, Firefox, Chrome, Opera, Safari
			xmlhttp = new XMLHttpRequest();
		} else {
			// code for IE6, IE5
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		}
		xmlhttp.onreadystatechange = function () {
				console.log(this.responseText);
			if (this.readyState == 4 && this.status == 200) {
				console.log(this.responseText);
				console.log("SUCCESS - send html_path: "+ url +" to the backend");
			} 
		};
		xmlhttp.open("GET", 'http://localhost:8080/CMSC424-MMDA/file_management.php?html_path=' + url, true);
		xmlhttp.send();
    });
}, false);
