<?php  //http://210.44.64.141:8080/reader/captcha.php
namespace Home\Controller;
class valite
{  
    private $word_width=8;
    private $word_hight=10;
    private $offset_x=6;
    private $offset_y=16;
    private $word_spacing=4;
	function setImage($image){
		$this->ImagePath=$image;
	}
    public function returnImage($flag)
    {
		$res = imagecreatefromgif($this->ImagePath); 
		if($flag){
			imagegif($res);}
    }
    public function getHec($output=0)  
    {   
		$res = imagecreatefromgif($this->ImagePath); 
        $size = array("60","36");  
		if($output){
			imagegif($res);
		}
        $data = array();  
        for($i=0; $i < $size[1]; ++$i)  
        {  
            for($j=0; $j < $size[0]; ++$j)  
            {  
                $rgb = imagecolorat($res,$j,$i);  
                $rgbarray = imagecolorsforindex($res, $rgb);  
                if($rgbarray['red'] < 125 || $rgbarray['green']<125  
                || $rgbarray['blue'] < 125)  
                {  
                    $data[$i][$j]=1;  
                }else{  
                    $data[$i][$j]=0;  
                }  
            }  
        }  
        $this->DataArray = $data;  
    }  
    public function run()  
    {  
        $result=""; 
        $data = array();  
        for($i=0;$i<4;++$i)  
        {  
            $x = ($i*($this->word_width+$this->word_spacing))+$this->offset_x;  
            $y = $this->offset_y;  
            for($h = $y; $h < ($this->offset_y+$this->word_hight); ++ $h)  
            {  
                for($w = $x; $w < ($x+$this->word_width); ++$w)  
                {  
                    $data[$i].=$this->DataArray[$h][$w];  
                }  
            }  
              
        }  
        foreach($data as $numKey => $numString)  
        {  
            $max=0.0;  
            $num = 0;  
            foreach($this->Keys as $key => $value)  
            {  
                $percent=0.0;  
                similar_text($value, $numString,$percent);  
                if(intval($percent) > $max)  
                {  
                    $max = $percent;  
                    $num = $key;  
                    if(intval($percent) > 95)  
                        break;  
                }  
            }  
            $result.=$num;  
        }  
        return $result;  
    }   
    public function __construct()  
    {  
        $this->Keys = array(  
        '0'=>'00011000001111000110011011000011110000111100001111000011011001100011110000011000',  
        '1'=>'00011000001110000111100000011000000110000001100000011000000110000001100001111110',  
        '2'=>'00111100011001101100001100000011000001100000110000011000001100000110000011111111',  
        '3'=>'01111100110001100000001100000110000111000000011000000011000000111100011001111100',  
        '4'=>'00000110000011100001111000110110011001101100011011111111000001100000011000000110',  
        '5'=>'11111110110000001100000011011100111001100000001100000011110000110110011000111100',  
        '6'=>'00111100011001101100001011000000110111001110011011000011110000110110011000111100',  
        '7'=>'11111111000000110000001100000110000011000001100000110000011000001100000011000000',  
        '8'=>'00111100011001101100001101100110001111000110011011000011110000110110011000111100',  
        '9'=>'00111100011001101100001111000011011001110011101100000011010000110110011000111100',  
    );  
    }  
    protected $ImagePath;  
    protected $DataArray;  
    protected $data;  
    protected $Keys;  
    protected $NumStringArray;  
}  
?>  