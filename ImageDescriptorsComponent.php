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

    protected function _matrix2YCbCr()
    {

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
        $n = $this->_width;
        $m = $this->_height;
        $sqrt_1o2 = sqrt(1/2);
        for ($k=0; $k < 3 ; $k++) { 
            for ($u=0; $u<$n; $u++) {
                for ($v=0; $v<$m; $v++) {
                    $sum = 0;
                    for ($i=0; $i<$n; $i++) {
                        for ($j=0;$j<$m; $j++) {
                            $m1 = ($i) ? 1: $sqrt_1o2;
                            $m2 = ($j) ? 1: $sqrt_1o2;
                            $cos1 = cos( ((pi()*$u) / (2*$n))*(2*$i+1));
                            $cos2 = cos( ((pi()*$v) / (2*$m))*(2*$j+1));
                            $f_ij = $this->_matrix[$i][$j][$k];
                            $sum += $m1 * $m2 * $cos1 * $cos2 * $f_ij;
                        }
                    }
                    $sum = sqrt(2/$n)*sqrt(2/$m)*$sum;
                    $dct[$u][$v][$k] = $sum;
                }
            }
        }
        return $dct;
    }
    
    public function CLD($image_url=null)
    {
        if ($image_url) {
            $this->image = $this->_loadImage($image_url);
        }
        $this->_partitioning();
        $this->_image2Matrix('ycbcr');
        $dct = $this->_DCT();
        $descritor = $this->_zigzag($dct);
    }

}
