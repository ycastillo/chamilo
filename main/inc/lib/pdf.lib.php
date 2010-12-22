<?php
/* See license terms in /license.txt */
/**
*   @package chamilo.library
*/
define('_MPDF_PATH', api_get_path(LIBRARY_PATH).'mpdf/');
require_once _MPDF_PATH.'mpdf.php';

class PDF {

    private function __construct() {
        
    }
    
    
    /**
     * Converts an html file to a pdf
     * @param   mixed  could be an html path or an array with html paths   
     * @param   string  course code 
     * @return  string  the pdf path
     */    
    public function html_to_pdf($html_file_array, $course_code = null) {  
        
        if (empty($html_file_array)) {
        	return false;
        }        
        if (is_array($html_file_array)) {
            if (count($html_file_array) == 0)
        	   return false;
        } else {
            if (!file_exists($html_file_array)) {
                return false;
            }
            //Converting the string into an array
            $html_file_array = array($html_file_array);
        }
        
        if (!empty($course_code)) {
        	$course_data = api_get_course_info($course_code);
        }
        
        //clean styles and javascript document
        $clean_search = array (
            '@<script[^>]*?>.*?</script>@si',
            '@<style[^>]*?>.*?</style>@siU'
            );
        //mPDF($codepage='win-1252',$format='A4',$default_font_size=0,$default_font='',$mgl=15,$mgr=15,$mgt=16,$mgb=16,$mgh=9,$mgf=9, $orientation='P')         
        $pdf = new mPDF('UTF-8', 'A4', '', '', 30, 20, 27, 25, 16, 13, 'P');
        
       // $pdf->SetBasePath($basehref);
               
        $pdf->directionality = api_get_text_direction(); // TODO: To be read from the html document.        
        $pdf->useOnlyCoreFonts = true;        
        $pdf->mirrorMargins = 1;            // Use different Odd/Even headers and footers and mirror margins
                
        $pdf->defaultheaderfontsize = 10;   // in pts
        $pdf->defaultheaderfontstyle = BI;   // blank, B, I, or BI
        $pdf->defaultheaderline = 1;        // 1 to include line below header/above footer        
        
        
        $my_header = self::get_header($course_code);
        $pdf->SetHeader($my_header);// ('{DATE j-m-Y}|{PAGENO}/{nb}|'.$title);
        
        $pdf->defaultfooterfontsize = 12;   // in pts
        $pdf->defaultfooterfontstyle = B;   // blank, B, I, or BI
        $pdf->defaultfooterline = 1;        // 1 to include line below header/above footer
                
        //@todo remove this and use a simpler way
        $footer = array (
          'odd' => array (
            'L' => array (
              'content' => '',
              'font-size' => 10,
              'font-style' => 'B',
              'font-family' => 'serif',
              'color'=>'#000000'
            ),
            'C' => array (
              'content' => '',
              'font-size' => 10,
              'font-style' => 'B',
              'font-family' => 'serif',
              'color'=>'#000000'
            ),
            'R' => array (
              'content' => '{PAGENO}',
              'font-size' => 10,
              'font-style' => 'B',
              'font-family' => 'serif',
              'color'=>'#000000'
            ),
            'line' => 1,
          ),
         'even' => array (
            'L' => array (
              'content' => '',
              'font-size' => 10,
              'font-style' => 'B',
              'font-family' => 'serif',
              'color'=>'#000000'
            ),
            'C' => array (
              'content' => '',
              'font-size' => 10,
              'font-style' => 'B',
              'font-family' => 'serif',
              'color'=>'#000000'
            ),
            'R' => array (
              'content' => '{PAGENO}',
              'font-size' => 10,
              'font-style' => 'B',
              'font-family' => 'serif',
              'color'=>'#000000'
            ),
            'line' => 1,
          ),
        );       
        
        $pdf->SetFooter($footer);      // defines footer for Odd and Even Pages - placed at Outer margin http://mpdf1.com/manual/index.php?tid=151&searchstring=setfooter

        if (api_get_setting('pdf_export_watermark_enable') == 'true') {
            $watermark_file = self::get_watermark($course_code);
            if (!empty($watermark_file)) {
                //http://mpdf1.com/manual/index.php?tid=269&searchstring=watermark
                $pdf->SetWatermarkImage($watermark_file);
                $pdf->showWatermarkImage = true;
            }
        }
       
        /*$pdf->SetAuthor('Documents Chamilo');
        $pdf->SetTitle('title');
        $pdf->SetSubject('Exported from Chamilo Documents');
        $pdf->SetKeywords('Chamilo Documents');
        */        
        foreach ($html_file_array as $html_file) {            
            if (!file_exists($html_file)) {
            	continue;
            }
            $file_info = pathinfo($html_file);
            $dirname = str_replace("\\", '/', $file_info['dirname']);
            $filename = $file_info['basename'];
            $filename =str_replace('_',' ',$filename);
            $extension = $file_info['extension'];        
            if (!($extension == 'html' || $extension == 'htm')) {
                return false;
            }        
            if ($extension == 'html'){
                $filename =basename($filename,'.html');
            } elseif($extension == 'htm'){
                $filename =basename($filename,'.htm');
            }
            
            $document_html = @file_get_contents($html_file);
            $document_html = preg_replace($clean_search, '', $document_html);   
            
            //absolute path for frames.css //TODO: necessary?
            $absolute_css_path=api_get_path(WEB_CODE_PATH).'css/'.api_get_setting('stylesheets').'/frames.css';
            $document_html=str_replace('href="./css/frames.css"',$absolute_css_path, $document_html);
            
            //$document_html=str_replace('<link rel="stylesheet" http://my.chamilo.net/main/css/chamilo/frames.css type="text/css" />','', $document_html);

            $document_html= str_replace('../','',$document_html);            
            $document_path = api_get_path(WEB_COURSE_PATH).$course_data['path'].'/document/';
                       
            $doc = new DOMDocument();           
            $result = @$doc->loadHTML($document_html);
                                 
            //Fixing only images @todo do the same thing with other elements
            $elements = $doc->getElementsByTagName('img');
            $replace_img_elements = array();
            if (!empty($elements)) {
                foreach($elements as $item) {                    
                    $old_src = $item->getAttribute('src');
                    //$old_src= str_replace('../','',$old_src);
                    if (strrpos('http', $old_src) === false) {
                        $document_html= str_replace($old_src, $document_path.$old_src, $document_html);                                                       	
                    }               
                }
            }
              
            //replace relative path by absolute path for resources
            //$document_html= str_replace('src="/chamilo/main/default_course_document/', 'temp_template_path', $document_html);// before save src templates not apply
            //$document_html= str_replace('src="/', 'temp_template_path', $document_html);// before save src templates not apply
            //$document_html= str_replace('src="/chamilo/main/default_course_document/', 'temp_template_path', $document_html);// before save src templates not apply
            
            //$src_http_www= 'src="'.api_get_path(WEB_COURSE_PATH).$course_data['path'].'/document/';
            //$document_html= str_replace('src="',$src_http_www, $document_html);
            //$document_html= str_replace('temp_template_path', 'src="/main/default_course_document/', $document_html);// restore src templates
            
            api_set_encoding_html($document_html, 'UTF-8'); // The library mPDF expects UTF-8 encoded input data.        
            $title = api_get_title_html($document_html, 'UTF-8', 'UTF-8');  // TODO: Maybe it is better idea the title to be passed through
                                                                            // $_GET[] too, as it is done with file name.
                                                                            // At the moment the title is retrieved from the html document itself.
            if (empty($title)) {
                $title = $filename; // Here file name is expected to contain ASCII symbols only.
            }            
            //var_dump($document_html);
            $pdf->WriteHTML($document_html,2);
        }
        $output_file = 'pdf_'.date('Y-m-d-his').'.pdf';
        $result = $pdf->Output($output_file, 'D');       /// F to save the pdf in a file              
        exit;
    }    
    
    /**
     * Gets the watermark from the platform or a course
     * @param   string  course code (optional)
     * @param   mixed   web path of the watermark image, false if there is nothing to return
     */
    public function get_watermark($course_code = null) {
        $web_path = false;
        if (!empty($course_code) && api_get_setting('pdf_export_watermark_by_course') == 'true') {
            $course_info = api_get_course_info($course_code);
            $store_path = api_get_path(SYS_COURSE_PATH).$course_info['path'].'/pdf_watermark.png';   // course path
            if (file_exists($store_path))
                $web_path   = api_get_path(WEB_COURSE_PATH).$course_info['path'].'/pdf_watermark.png';
        } else {
            $store_path = api_get_path(SYS_CODE_PATH).'default_course_document/'.api_get_current_access_url_id().'_pdf_watermark.png';   // course path
            if (file_exists($store_path))                   
                $web_path   = api_get_path(WEB_CODE_PATH).'default_course_document/'.api_get_current_access_url_id().'_pdf_watermark.png';
        }
        return $web_path;        
    }
    
    /**
     * Deletes the watermark from the Platform or Course
     * @param   string  course code (optional)
     * @param   mixed   web path of the watermark image, false if there is nothing to return
     */
     
    public function delete_watermark($course_code = null) {        
        if (!empty($course_code) && api_get_setting('pdf_export_watermark_by_course') == 'true') {
            $course_info = api_get_course_info($course_code);
            $store_path = api_get_path(SYS_COURSE_PATH).$course_info['path'].'/pdf_watermark.png';   // course path            
        } else {
            $store_path = api_get_path(SYS_CODE_PATH).'default_course_document/'.api_get_current_access_url_id().'_pdf_watermark.png';   // course path              
        }        
        if (file_exists($store_path)) {
            @unlink($store_path);
            return true;
        }
    	return false;
    }
    
    /**
     * Uploads the pdf watermark 
     */
    public function upload_watermark($filename, $source_file, $course_code = null) {        
        if (!empty($course_code) && api_get_setting('pdf_export_watermark_by_course') == 'true') {
            $course_info = api_get_course_info($course_code);
            $store_path = api_get_path(SYS_COURSE_PATH).$course_info['path'];   // course path
            $web_path   = api_get_path(WEB_COURSE_PATH).$course_info['path'].'pdf_watermark.png';
        } else {
            $store_path = api_get_path(SYS_CODE_PATH).'default_course_document';   // course path	
            $web_path   = api_get_path(WEB_CODE_PATH).'default_course_document/'.api_get_current_access_url_id().'_pdf_watermark.png';
        }
        
        $course_image = $store_path.'/'.api_get_current_access_url_id().'_pdf_watermark.png';
        $extension = strtolower(substr(strrchr($filename, '.'), 1));
        $result = false;
        $allowed_picture_types = array ('jpg', 'jpeg', 'png', 'gif');
        if (in_array($extension, $allowed_picture_types)) {
            if (file_exists($course_image)) {
                @unlink($course_image);
            }            
            if ($extension != 'png') {
                // convert image to png extension
                if ($extension == 'jpg' || $extension == 'jpeg') {
                    $image = imagecreatefromjpeg($source_file);
                } else {
                    $image = imagecreatefromgif($source_file);
                }
                ob_start();
                imagepng($image);
                $imagevariable = ob_get_contents();
                ob_end_clean();
                // save picture
                if (@file_put_contents($course_image, $imagevariable)) {
                    $result = true;
                }
            } else {
                $result = @move_uploaded_file($source_file, $course_image);
            }
        }
        if ($result) {
        	$result = $web_path;
        }
        return $result;
    }
    /**
     * Returns the default header
     */
    public function get_header($course_code = null) {
        $header = '';
        
    	if (!empty($course_code) && api_get_setting('pdf_export_watermark_by_course') == 'true') {
            $header = api_get_course_setting('pdf_export_watermark_text');                
        } else {
        	$header = api_get_setting('pdf_export_watermark_text');
        }
        return $header;
        
    } 
}
?>