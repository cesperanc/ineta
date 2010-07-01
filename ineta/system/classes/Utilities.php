<?php
	/**
	 * Utilities Class
	 * 
	 * 
	 * @author Cláudio Esperança <claudio.esperanca@ipleiria.pt>
	 * @version 1.0 
	 */

	class Utilities {
    	public static function javascriptRedirect($location='./', $noscript=false){
    		$result = "<script type=\"text/javascript\">window.location = \"$location\";</script>";
			$result .= ($noscript?"<noscript>$noscript</noscript>":'');
			return $result;
        }
    	
	}
?>
