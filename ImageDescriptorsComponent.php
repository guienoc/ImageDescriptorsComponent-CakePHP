<?php
namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;

/**
 * ImageDescriptors component
 */
class ImageDescriptorsComponent extends Component
{
    protected $_defaultConfig = [];
    protected $_image = null;
    protected $_width = null; 
    protected $_height = null;
    protected $_matrix = null;
    

    protected function _loadImage($image_url=null)
    {
        $this->_image = imagecreatefromjpeg($image_url);
        $this->_width = imagesx($this->_image);
        $this->_height = imagesy($this->_image);
    }

    protected function _getRGB($x,$y)
    {
        $rgb = imagecolorat($this->_image, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        return [$r,$g,$b];
    }

    protected function _image2Matrix($spaceColor='rgb')
    {
        for ($i=0; $i < $this->_width ; $i++) { 
            for ($j=0; $j < $this->_height; $j++) {
                $rgb = $this->_getRGB($i,$j);
                if ($spaceColor=='ycbcr') {
                    $this->_matrix[$i][$j] = $this->_RGB2YCbCr($rgb);
                } else {
                    $this->_matrix[$i][$j] = $rgb;
                }
            }
        }
    }

    protected function _partitioning()
    {
        $new_image = imagecreatetruecolor(8,8);
        imagecopyresampled($new_image,$this->_image,0,0,0,0,8,8,$this->_width,$this->_height);
        $this->_image = $new_image;
        $this->_width = 8;
        $this->_height = 8;
    }

    public function _RGB2YCbCr($rgb=[])
    {
        $Y = 16 + 65.738*$rgb[0]/256 + 129.057*$rgb[1]/256 + 25.064*$rgb[2]/256;
        $Cb = 128 - 37.945*$rgb[0]/256 - 74.494*$rgb[1]/256 + 112.439*$rgb[2]/256;
        $Cr = 128 + 112.439*$rgb[0]/256 - 94.154*$rgb[1]/256 - 18.285*$rgb[2]/256;
        return [round($Y), round($Cb), round($Cr)];
    }

    protected function _zigzag($dct=[])
    {
        $zigzag = [1,2,9,17,10,3,4,11,18,25,33,26,19,12,5,6,13,29,27,34,41,49,42,35,28,21,14,7,8,15,22,29,36,43,50,57,58,51,44,37,30,23,16,24,31,38,45,52,59,60,53,46,39,32,49,47,54,61,62,55,48,56,63,64];
        $descritor = [];
        foreach ($zigzag as $k => $i) {
            $x = (int) (($i-1)/$this->_width);
            $y = (int) (($i-1)%$this->_height);
            $descritor[$k] = $dct[$x][$y];
        }
        return $descritor;
    }
    protected function _DCT()
    {
        $dct = [];
        $M = $this->_width;
        $N = $this->_height;
        for ($k=0; $k < 3; $k++) { 
            for ($p=0; $p < $M ; $p++) { 
                for ($q=0; $q < $N ; $q++) { 
                    $a1 = ($p) ? sqrt(2/$M): 1/sqrt($M);
                    $a2 = ($q) ? sqrt(2/$N): 1/sqrt($N);
                    $sum = 0;
                    for ($m=0; $m < $M; $m++) { 
                        for ($n=0; $n < $N; $n++) { 
                            $sum = $this->_matrix[$m][$n][$k] * cos(pi()*(2*$m+1)*$p/(2*$M)) * cos(pi()*(2*$n+1)*$q/(2*$N));
                        }
                    }
                    $dct[$p][$q][$k] = $a1 * $a2 * $sum;
                }
            }
        }
        return $dct;
    }
    
    public function CLDMatching($d1=[],$d2=[])
    {
        $count = count($d1);
        $sum = 0;
        for ($i=0; $i<$count; $i++) {
            $sum = sqrt(pow($d1[$i][0]-$d2[$i][0], 2)) + sqrt(pow($d1[$i][1]-$d2[$i][1], 2)) + sqrt(pow($d1[$i][2]-$d2[$i][2], 2));
        }
        return $sum;
    }

    public function CLD($image_url=null)
    {
        if ($image_url) {
            $this->image = $this->_loadImage($image_url);
        }
        $this->_partitioning();
        $this->_image2Matrix('ycbcr');
        $dct = $this->_DCT();
        return $this->_zigzag($dct);
    }

}
