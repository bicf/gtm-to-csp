<?php

class converter{
    public static $ALGOS = ['sha512','sha384','sha256',];
    /**
     * @param null $name file to parse
     */
    public function run($gtmjson,$outdir,$includeUri)
    {
        $_obj = json_decode($gtmjson);

        $_rv = [];
        $_rv['elements'] = [];
        $_rv['json'] = null;

        $_dir = $outdir;
        $_include = $includeUri;

        if(!is_dir($_dir)){
            mkdir($_dir);
        }
        foreach ($_obj->containerVersion->tag as $item){
            $_rvElement =[];
            if($item->type == 'html'){
                $_rvElement['tagId'] = $item->tagId;
                $_rvElement['name'] = $item->name;
                $_rvElement['scripts'] = [];

                foreach ($item->parameter as $html){
                    if($html->type == 'TEMPLATE' && $html->key =='html'){
                        $_js = $html->value;
                        $_res = preg_match_all('#<(((script|noscript)[^>]*)>(.*)</\3\s*>)#simU',$_js,$_match,PREG_SET_ORDER);
                        if(!$_res){
                            // nothing to do
                            continue;
                        }
                        $_elementsFound=0;
                        $_search=[];
                        $_replace=[];
                        foreach ($_match as $_elems){
                            $_script=[];
                            if(0 != strcasecmp($_elems[3], 'script')){
                                continue;
                            }
                            if(strstr($_elems[2],'src=')){
                                continue;
                            }
                            $_hasType= preg_match('#type\s*=\s*([\'"])(.*)/(.*)\1#is',$_elems[2],$_matchType);
                            if($_hasType && $_matchType[3] != 'javascript'){
                                continue;
                            }
                            $_search[] = $_elems[0];
                            $_innerJs = $_elems[4];
                            $_signatures = [];
                            $_raw = "";

                            foreach (self::$ALGOS as $_algo){
                                $_raw = hash($_algo,$_innerJs,true);
                                $_signatures[] = sprintf("%s-%s",$_algo,base64_encode($_raw));

                            }

                            $_hexString = bin2hex($_raw);
                            $_uri = sprintf("%s/%s.js",$_include,$_hexString);
                            $filename = sprintf("%s/%s.js",$_dir,$_hexString);
                            $_replace[]=sprintf("<script type=\"text/javascript\" src=\"%s\"></script>\n",$_uri);

                            $_script['filename'] =$filename;
                            $_script['signatures'] =$_signatures;
                            $_script['uri'] =$_uri;

                            file_put_contents($filename,$_innerJs);
                            $_elementsFound++;

                            $_rvElement['scripts'][] = $_script;
                        }

                        if($_elementsFound==0){
                            // no javascript found
                            continue;
                        }

                        $_res = str_replace($_search,$_replace,$_js);
                        $html->value = $_res;
                        $_rv['elements'][] = $_rvElement;
                    }
                }
            }
        }
        $_rv['json'] = json_encode($_obj);
        return $_rv;
    }

    public function index()
    {
        $_rv = <<< HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GTM to CSP</title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
</head>
<body>

<div class="container">

<div class="row">
<div class="col col-md-6"></div>
    <form action="" method="POST" enctype="multipart/form-data">
        <div class="form-group"> <input type="hidden" name="MAX_FILE_SIZE" value="1000000"> </div>
        <div class="form-group">
            <label for="outDir">Save path</label>
            <input type="text" name="outDir" value="" class="form-control" id="outDir" placeholder="Save path">
        </div>
    
        <div class="form-group">
            <label for="scriptPrefix">Script source prefix eg: /js or https://example.com/js </label>
            <input type="text" name="scriptPrefix" value="" class="form-control" id="scriptPrefix" placeholder="Scrip prefix">
        </div>
    
        <div class="form-group">
            <label for="conf">GTM json eg: GTM-ABCDEF_v42.json </label>
            <input type="file" name="conf" value="" class="form-control" id="conf" placeholder="GTM json">
        </div>
    
        <div class="form-group">
            <button type="submit" class="btn btn-default">Preview</button>
        </div>
    </form>
</div>
</div>

</div>



</body>
</html>
HTML;

        return $_rv;
    }

    /**
     * @param $source
     * @param $outDir
     * @param $scriptPrefix
     * @param $response
     * @return string
     */
    public function preview($source, $outDir, $scriptPrefix, $response)
    {
        $_destination = base64_encode($response['json']);

        $_infos="";
        foreach ($response['elements'] as $_element){
            $_infos .= sprintf("%s: %s\n",$_element['tagId'],$_element['name']);
            foreach ($_element['scripts'] as $_include){
                $_script = sprintf("<script type=\"text/javascript\" src=\"%s\"></script> ",$_include['uri']);
                $_infos .= sprintf("Filename:\n %s\nScript:\n %s \n",$_include['filename'],$_script);
                foreach ($_include['signatures'] as $_signature){
                    $_infos .= sprintf("'%s'\n",$_signature);
                }
                $_infos .="\n\n";
            }
        }
        $_infos = "<pre>\n".htmlspecialchars($_infos)."</pre>";
        $_rv = /** lang html */ <<< HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GTM to CSP</title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
</head>
<body>
<div class="container">

<div class="row">
<div class="col col-md-6">
    <form action="" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="jsonSrc" value="$source">
        <input type="hidden" name="jsonDst" value="$_destination">
         <br>
    
        <div class="form-group">
            <label for="outDir">Save path</label>
            <input type="text" name="outDir" value="$outDir" class="form-control" id="outDir" placeholder="Save path">
        </div>
    
        <div class="form-group">
            <label for="scriptPrefix">Script source prefix eg: /js or https://example.com/js </label>
            <input type="text" name="scriptPrefix" value="$scriptPrefix" class="form-control" id="scriptPrefix" placeholder="Scrip prefix">
        </div>
    
        <div class="form-group">
            <button type="submit" class="btn btn-default">Preview</button>
            <input class="btn btn-success" name="download" type="submit" value="Download">
        </div>
    </form>
</div>
</div>

<div class="row">
<div class="col col-md-12">
$_infos
</div>
</div>

</div>

</body>
</html>
HTML;

        return $_rv;
    }
}

$_o = new converter();
switch ($_SERVER['REQUEST_METHOD']){

    case 'GET':
            echo $_o->index();
        break;

    case 'POST':
        $a=1;
        if(isset($_FILES['conf'])){
            $_json = file_get_contents($_FILES['conf']['tmp_name']);
            $_jsonSrc = base64_encode($_json);
            $_outDir = $_POST['outDir'];
            $_scriptPrefix = $_POST['scriptPrefix'];
        }

        if(isset($_POST['jsonSrc'])){
            $_jsonSrc = $_POST['jsonSrc'];
            $_json = base64_decode($_jsonSrc);
            $_outDir = $_POST['outDir'];
            $_scriptPrefix = $_POST['scriptPrefix'];
        }

        if(isset($_POST['download'])){
            $file = "gtm-".date('Ymd-His').".json";
            header("Cache-Control: public");
            header("Content-Description: File Transfer");
            header("Content-Disposition: attachment; filename=" . $file);
            header("Content-Transfer-Encoding: binary");
            header('Content-type: application/json');

            die(base64_decode($_POST['jsonDst']));
        }

        $_res = $_o->run($_json,$_outDir,$_scriptPrefix);
        echo $_o->preview($_jsonSrc,$_outDir,$_scriptPrefix,$_res);

        break;

}