function showMeet() {
    /* toggle meet table */
    var content = document.getElementById("meetList").innerHTML;
    if (content == '') {
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
          if (this.readyState == 4 && this.status == 200) {
            document.getElementById("meetList").innerHTML = this.responseText;
          }
        };
        xmlhttp.open("GET", "ajax.php?q=meet", true);
        xmlhttp.send();
    } else {
        document.getElementById("meetList").innerHTML = '';
    }
}
