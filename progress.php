<?php

/**
 * @package    gallery
 */

headScript::setJavascript("/js/jquery-1.3.2.min.js");
headScript::setJavascript("/js/apcquery.js");
headScript::setCss("/css/apcquery.css");

?>
<div>
<form enctype="multipart/form-data" method="post" action="/upload/apcUpload.php?redirect=/gallery/index">
<fieldset>
<legend>Please choose a file</legend>
<label for="user-file">your file</label>
<input type="file" id="user-file" name="user-file" tabindex="1" />
<input type="submit" value="Upload"  tabindex="2" />
<div class="apcquery">
<span class="info">Total</span><span class="total"></span>
<span class="info">Sent</span><span class="loaded"></span>
<span class="info">Rate</span><span class="rate"></span>
<span class="percent"></span>
</div>
</fieldset>
</form>
</div>

