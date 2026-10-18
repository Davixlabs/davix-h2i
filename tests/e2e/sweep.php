<?php
wp_set_current_user(1);
use DavixWP\Operations\Executor;
function run($op,$req){ $req['operation']=$op; $req['source']='tab'; $o=Executor::run($req); echo str_pad($op,36).($o['ok']?'OK   ':'FAIL ').substr($o['message'],0,110).(!empty($o['error']['code'])?' ['.$o['error']['code'].']':'').' out='.count($o['outputs'])."\n"; return $o; }
$img=5;
// a pdf from render
$o=run('render.html-to-pdf',['params'=>['html'=>'<h1>Page</h1><p>hello</p>','format'=>'A4']]);
$pdf=!empty($o['attachment_ids'][0])?$o['attachment_ids'][0]:0; echo "pdf attachment: $pdf\n";
$o2=run('render.html-to-pdf',['params'=>['html'=>'<h1>Second</h1>']]); $pdf2=!empty($o2['attachment_ids'][0])?$o2['attachment_ids'][0]:0;
// second image
$im=imagecreatetruecolor(800,600); imagefill($im,0,0,imagecolorallocate($im,20,120,200)); $p=sys_get_temp_dir().'/b.png'; imagepng($im,$p);
$r=\DavixWP\Media\AttachmentWriter::import($p,['filename'=>'b.png','mime'=>'image/png','keep_source'=>true]); $img2=$r['id'];
$S=['object_id'=>$img];
run('images.optimize',$S+['params'=>['quality'=>70]]);
run('images.auto-optimize',$S);
run('images.convert',$S+['params'=>['output_format'=>'webp'],'mode'=>'copy']);
run('images.resize',$S+['params'=>['width'=>600],'mode'=>'copy']);
run('images.transform',$S+['params'=>['rotate.angle'=>90,'enhance.grayscale'=>true],'mode'=>'copy']);
run('images.pipeline',$S+['params'=>['operations'=>[['op'=>'resize','width'=>500],['op'=>'optimize','quality'=>70]]],'mode'=>'copy']);
run('images.watermark',$S+['params'=>['watermark'=>['text'=>'Test','position'=>'center']],'mode'=>'copy']);
run('images.remove-background',['object_id'=>$img2]);
run('images.metadata',$S);
run('images.metadata-edit',$S+['params'=>['mode'=>'merge','strip_gps'=>true,'author'=>'Tester']]);
run('images.variants',$S+['params'=>['variants'=>[['name'=>'s','width'=>300],['name'=>'m','width'=>600]]]]);
run('images.inspect',['object_ids'=>[$img,$img2],'params'=>['checks'=>['dimensions','hash','similarity']]]);
run('images.compare',['object_id'=>$img,'extra'=>['imageB'=>$img2]]);
run('images.favicon',['object_id'=>$img2]);
run('images.qr',['params'=>['text'=>'https://example.com']]);
run('render.html-to-image',['params'=>['html'=>'<h1>x</h1>','width'=>400,'height'=>200]]);
run('render.chart',['params'=>['type'=>'bar','data'=>[['label'=>'A','value'=>3],['label'=>'B','value'=>5]],'x'=>'label','y'=>'value','output'=>'png']]);
if($pdf){
 run('pdfs.optimize',['object_id'=>$pdf,'mode'=>'copy']);
 run('pdfs.to-images',['object_id'=>$pdf,'params'=>['pages'=>'1','format'=>'png']]);
 run('pdfs.split',['object_id'=>$pdf,'params'=>['mode'=>'every','every'=>1]]);
 run('pdfs.extract',['object_id'=>$pdf,'params'=>['pages'=>'1']]);
 run('pdfs.watermark',['object_id'=>$pdf,'params'=>['watermarkText'=>'X']]);
 run('pdfs.security',['object_id'=>$pdf,'params'=>['operation'=>'encrypt','userPassword'=>'abc12345','ownerPassword'=>'owner12345']]);
 run('documents.convert',['object_id'=>$pdf,'params'=>['to'=>'png']]);
 if($pdf2) run('pdfs.merge',['object_ids'=>[$pdf,$pdf2]]);
}
foreach(['ai.content.alt-text'=>['object_id'=>$img],'ai.content.caption'=>['object_id'=>$img],'ai.content.tags'=>['object_id'=>$img],'ai.images.moderate'=>['object_id'=>$img],'ai.content.seo'=>['params'=>['text'=>'A post about cats']],'ai.documents.summarize'=>['params'=>['text'=>'Long text about many things.']],'ai.content.title'=>['params'=>['text'=>'cats']],'ai.content.social-post'=>['params'=>['text'=>'cats','platform'=>'twitter']],'ai.content.product-description'=>['params'=>['text'=>'blue mug','product_name'=>'Mug']],'ai.documents.translate'=>['params'=>['text'=>'hello','target_language'=>'fr']],'ai.documents.correct'=>['params'=>['text'=>'ths is wrong']],'ai.generation.generate'=>['params'=>['instructions'=>'a red apple']],'ai.generation.background'=>['params'=>['instructions'=>'beach']],'ai.generation.product'=>['object_id'=>$img],'ai.generation.social'=>['params'=>['headline'=>'Hi']],'ai.generation.thumbnail'=>['params'=>['title'=>'Hi']],'ai.enhancement.upscale'=>['object_id'=>$img]] as $op=>$rq) run($op,$rq);
