<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=ISO-8859-I">
	<title>Paytm</title>
	<script type="text/javascript">
		function response(){
			return document.getElementById('response').value;
		}
	</script>
</head>
<body>
  Redirect back to the app<br>

  <form name="frm" method="post">
    <input type="hidden" id="response" name="responseField" value='<?php echo $encoded_json?>'>
  </form>
</body>
</html>