<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Example shortcode output for document-intake
function xpressui_render_document_intake() {
    $iframe_url = home_url('/?xpressui_shell=document-intake');
    ob_start();
    ?>
<div class="xpressui-wrapper" style="width: 100%; margin: 0 auto; clear: both; overflow: hidden; box-sizing: border-box; padding: 2px;">
    <iframe
        id="xpressui_iframe_document_intake"
        src="<?php echo esc_url($iframe_url); ?>"
        style="width: 100%; border: none; min-height: 600px; display: block; overflow: hidden; box-sizing: border-box;"
        scrolling="no"
        allow="camera; microphone; fullscreen; autoplay"
    ></iframe>
</div>
<script>
(function(){var e=document.getElementById("xpressui_iframe_document_intake");if(!e)return;var t=function(){try{var n=e.contentDocument||e.contentWindow.document;if(!n||!n.body)return;var r=n.querySelector(".page-shell")||n.body,i=n.querySelector(".form-frame")||r;if(i&&i.offsetHeight>0){var a=Math.ceil(i.offsetHeight+20),d=parseInt(e.style.height||"0",10);if(Math.abs(d-a)>4){e.style.height=a+"px";if(d>0&&d-a>100){var s=e.getBoundingClientRect();if(s.top<0)window.scrollBy({top:s.top-80,behavior:"smooth"})}}}}catch(e){}};t();setInterval(t,250);window.addEventListener("resize",t)})();
</script>
    <?php
    return ob_get_clean();
}

add_shortcode('xpressui_document_intake', 'xpressui_render_document_intake');
