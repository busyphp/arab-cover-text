<?php

namespace BusyPHP\helper;

use BusyPHP\helper\child\UG;

/**
 * 阿拉伯字符修正
 * @author busy^life <busy.life@qq.com>
 * @copyright (c) 2015--2021 ShanXi Han Tuo Technology Co.,Ltd. All rights reserved.
 * @version $Id: 2021/10/22 下午下午12:04 ArabCoverHelper.php $
 */
class ArabCoverHelper
{
    const BASELEN   = 256;
    
    const WDBEG     = 0;
    
    const INBEG     = 1;
    
    const NOBEG     = 2;
    
    const BPAD      = 0x0600; // start and end points for Arabic basic range
    
    const BMAX      = 0x06FF;
    
    const EPAD      = 0xFB00; // presentation form region (extented region)
    
    const EMAX      = 0xFEFF;
    
    const CPAD      = 0x0400; // cyrillic
    
    const CMAX      = 0x04FF; // cyrillic
    
    const CHEE      = 0x0686;
    
    const GHEE      = 0x063A;
    
    const NGEE      = 0x06AD;
    
    const SHEE      = 0x0634;
    
    const SZEE      = 0x0698;
    
    const LA        = 0xFEFB;
    
    const _LA       = 0xFEFC;
    
    const HAMZA     = 0x0626;
    
    const RCQUOTE   = 0x2019;
    
    const RCODQUOTE = 0x201C;
    
    const RCCDQUOTE = 0x201D;
    
    const PRIMe     = 233; // 'e
    
    const PRIME     = 201; // 'E
    
    const COLo      = 246; // :o
    
    const COLO      = 214; // :O
    
    const COLu      = 252; // :u
    
    const COLU      = 220; // :U
    
    /**
     * @var array
     */
    private $cmList = [];
    
    /**
     * @var array
     */
    private $cmApiNvList = [];
    
    /**
     * @var UG[]
     */
    private $pFormList = [];
    
    /**
     * @var array
     */
    private $pfToBasic = [];
    
    
    /**
     * Cover constructor.
     */
    public function __construct()
    {
        $this->init();
    }
    
    
    /**
     * 执行转换
     * @param string $string
     * @return string
     */
    public function convert($string = '')
    {
        $string = trim($string);
        if (!is_string($string)) {
            return '';
        }
        
        $cm    = $this->pFormList[$this->cmUG('l')];
        $bt    = self::WDBEG;
        $prev  = null;
        $ppfwc = null;
        $j     = 0;
        $list  = [];
        
        for ($i = 0; $i < mb_strlen($string); $i++) {
            $list[$i] = null;
        }
        
        for ($i = 0; $i < mb_strlen($string); $i++) {
            $word = $this->getAsciiCodeAt($string, $i);
            if (self::BPAD <= $word && $word < self::BMAX) {
                $syn = $this->pFormList[$word - self::BPAD];
                if ($syn != null) {
                    switch ($bt) {
                        case self::INBEG:
                        case self::WDBEG:
                            $pfwc = $syn->iForm;
                        break;
                        case self::NOBEG:
                            $pfwc = $syn->eForm;
                        break;
                    }
                    
                    
                    // previous letter does not ask for word-beginning form,
                    // and we have to change it to either medial or beginning form,
                    // depending on the previous letter's current form.
                    // this means the previous letter was a joinable Uyghur letter
                    if ($bt != self::WDBEG) {
                        $tsyn = $this->pFormList[$prev - self::BPAD];
                        
                        // special cases for LA and _LA
                        if ($ppfwc == $cm->iForm && $word == $this->cmList[$this->getAsciiCodeAt('a')]) {
                            $list[$j - 1] = self::LA;
                            $bt           = self::WDBEG;
                            continue;
                        } else if ($ppfwc == $cm->eForm && $word == $this->cmList[$this->getAsciiCodeAt('a')]) {
                            $list[$j - 1] = self::_LA;
                            $bt           = self::WDBEG;
                            continue;
                        }
                        
                        // update previous character
                        if ($ppfwc == $tsyn->iForm) {
                            $list[$j - 1] = $tsyn->bForm;
                        } else if ($ppfwc == $tsyn->eForm) {
                            $list[$j - 1] = $tsyn->mForm;
                        }
                    }
                    $bt = $syn->bType; // we will need this in next round
                }
                
                //
                // a non-Uyghur char in basic range
                else {
                    $pfwc = $word;
                    $bt   = self::WDBEG;
                }
            }
            
            //
            // not in basic Arabic range ( 0x0600-0x06FF )
            else {
                $pfwc = $word;
                $bt   = self::WDBEG;
            }
            
            $list[$j] = $pfwc;
            $ppfwc    = $pfwc; // previous presentation form wide character
            $prev     = $word;
            $j++;
        }
        
        
        for ($i = 0; $i < $j; $i++) {
            $list[$i] = $this->asciiToString($list[$i]);
        }
        
        return implode('', $list);
    }
    
    
    /**
     * 初始化
     */
    protected function init()
    {
        // zero-out all entries first
        for ($i = 0; $i < self::BASELEN; $i++) {
            $this->cmList[$i] = 0;
        }
        
        $this->cmList[$this->getAsciiCodeAt('a')] = 0x0627;
        $this->cmList[$this->getAsciiCodeAt('b')] = 0x0628;
        $this->cmList[$this->getAsciiCodeAt('c')] = 0x0643;
        $this->cmList[$this->getAsciiCodeAt('d')] = 0x062F;
        $this->cmList[$this->getAsciiCodeAt('e')] = 0x06D5;
        $this->cmList[$this->getAsciiCodeAt('f')] = 0x0641;
        $this->cmList[$this->getAsciiCodeAt('g')] = 0x06AF;
        $this->cmList[$this->getAsciiCodeAt('h')] = 0x06BE;
        $this->cmList[$this->getAsciiCodeAt('i')] = 0x0649;
        $this->cmList[$this->getAsciiCodeAt('j')] = 0x062C;
        $this->cmList[$this->getAsciiCodeAt('k')] = 0x0643;
        $this->cmList[$this->getAsciiCodeAt('l')] = 0x0644;
        $this->cmList[$this->getAsciiCodeAt('m')] = 0x0645;
        $this->cmList[$this->getAsciiCodeAt('n')] = 0x0646;
        $this->cmList[$this->getAsciiCodeAt('o')] = 0x0648;
        $this->cmList[$this->getAsciiCodeAt('p')] = 0x067E;
        $this->cmList[$this->getAsciiCodeAt('q')] = 0x0642;
        $this->cmList[$this->getAsciiCodeAt('r')] = 0x0631;
        $this->cmList[$this->getAsciiCodeAt('s')] = 0x0633;
        $this->cmList[$this->getAsciiCodeAt('t')] = 0x062A;
        $this->cmList[$this->getAsciiCodeAt('u')] = 0x06C7;
        $this->cmList[$this->getAsciiCodeAt('v')] = 0x06CB;
        $this->cmList[$this->getAsciiCodeAt('w')] = 0x06CB;
        $this->cmList[$this->getAsciiCodeAt('x')] = 0x062E;
        $this->cmList[$this->getAsciiCodeAt('y')] = 0x064A;
        $this->cmList[$this->getAsciiCodeAt('z')] = 0x0632;
        
        
        $this->cmList[self::PRIMe] = 0x06D0; // 'e
        $this->cmList[self::PRIME] = 0x06D0; // 'E
        $this->cmList[self::COLo]  = 0x06C6; // :o
        $this->cmList[self::COLO]  = 0x06C6; // :O
        $this->cmList[self::COLu]  = 0x06C8; // :u
        $this->cmList[self::COLU]  = 0x06C8; // :U
        
        for ($i = 0; $i < count($this->cmList); $i++) {
            if ($this->cmList[$i] != 0) {
                $u = $this->getAsciiCodeAt(strtoupper($this->asciiToString($i)));
                if ($this->cmList[$u] == 0) {
                    $this->cmList[$u] = $this->cmList[$i];
                }
            }
        }
        
        // Uyghur punctuation marks
        $this->cmList[$this->getAsciiCodeAt(';')] = 0x061B;
        $this->cmList[$this->getAsciiCodeAt('?')] = 0x061F;
        $this->cmList[$this->getAsciiCodeAt(',')] = 0x060C;
        
        for ($i = 0; $i < self::BASELEN; $i++) {
            $wc = $this->cmList[$i];
            if ($wc != 0) {
                $this->cmApiNvList[$wc - self::BPAD] = $i;
            } else {
                $this->cmApiNvList[$i] = null;
            }
        }
        
        // S new_syn ( wchar_t i, wchar_t b, wchar_t m, wchar_t e, begtype bt ) ;
        for ($i = 0; $i < self::BASELEN; $i++) {
            $this->pFormList[$i] = null;
        }
        
        $this->pFormList[$this->cmUG('a')]        = new UG(0xFE8D, 0xFE8D, 0xFE8D, 0xFE8E, self::WDBEG);
        $this->pFormList[$this->cmUG('e')]        = new UG(0xFEE9, 0xFEE9, 0xFEE9, 0xFEEA, self::WDBEG);
        $this->pFormList[$this->cmUG('b')]        = new UG(0xFE8F, 0xFE91, 0xFE92, 0xFE90, self::NOBEG);
        $this->pFormList[$this->cmUG('p')]        = new UG(0xFB56, 0xFB58, 0xFB59, 0xFB57, self::NOBEG);
        $this->pFormList[$this->cmUG('t')]        = new UG(0xFE95, 0xFE97, 0xFE98, 0xFE96, self::NOBEG);
        $this->pFormList[$this->cmUG('j')]        = new UG(0xFE9D, 0xFE9F, 0xFEA0, 0xFE9E, self::NOBEG);
        $this->pFormList[self::CHEE - self::BPAD] = new UG(0xFB7A, 0xFB7C, 0xFB7D, 0xFB7B, self::NOBEG);
        $this->pFormList[$this->cmUG('x')]        = new UG(0xFEA5, 0xFEA7, 0xFEA8, 0xFEA6, self::NOBEG);
        $this->pFormList[$this->cmUG('d')]        = new UG(0xFEA9, 0xFEA9, 0xFEAA, 0xFEAA, self::INBEG);
        $this->pFormList[$this->cmUG('r')]        = new UG(0xFEAD, 0xFEAD, 0xFEAE, 0xFEAE, self::INBEG);
        $this->pFormList[$this->cmUG('z')]        = new UG(0xFEAF, 0xFEAF, 0xFEB0, 0xFEB0, self::INBEG);
        $this->pFormList[self::SZEE - self::BPAD] = new UG(0xFB8A, 0xFB8A, 0xFB8B, 0xFB8B, self::INBEG);
        $this->pFormList[$this->cmUG('s')]        = new UG(0xFEB1, 0xFEB3, 0xFEB4, 0xFEB2, self::NOBEG);
        $this->pFormList[self::SHEE - self::BPAD] = new UG(0xFEB5, 0xFEB7, 0xFEB8, 0xFEB6, self::NOBEG);
        $this->pFormList[self::GHEE - self::BPAD] = new UG(0xFECD, 0xFECF, 0xFED0, 0xFECE, self::NOBEG);
        $this->pFormList[$this->cmUG('f')]        = new UG(0xFED1, 0xFED3, 0xFED4, 0xFED2, self::NOBEG);
        $this->pFormList[$this->cmUG('q')]        = new UG(0xFED5, 0xFED7, 0xFED8, 0xFED6, self::NOBEG);
        $this->pFormList[$this->cmUG('k')]        = new UG(0xFED9, 0xFEDB, 0xFEDC, 0xFEDA, self::NOBEG);
        $this->pFormList[$this->cmUG('g')]        = new UG(0xFB92, 0xFB94, 0xFB95, 0xFB93, self::NOBEG);
        $this->pFormList[self::NGEE - self::BPAD] = new UG(0xFBD3, 0xFBD5, 0xFBD6, 0xFBD4, self::NOBEG);
        $this->pFormList[$this->cmUG('l')]        = new UG(0xFEDD, 0xFEDF, 0xFEE0, 0xFEDE, self::NOBEG);
        $this->pFormList[$this->cmUG('m')]        = new UG(0xFEE1, 0xFEE3, 0xFEE4, 0xFEE2, self::NOBEG);
        $this->pFormList[$this->cmUG('n')]        = new UG(0xFEE5, 0xFEE7, 0xFEE8, 0xFEE6, self::NOBEG);
        $this->pFormList[$this->cmUG('h')]        = new UG(0xFBAA, 0xFBAA, 0xFBAD, 0xFBAD, self::NOBEG);
        $this->pFormList[$this->cmUG('o')]        = new UG(0xFEED, 0xFEED, 0xFEEE, 0xFEEE, self::INBEG);
        $this->pFormList[$this->cmUG('u')]        = new UG(0xFBD7, 0xFBD7, 0xFBD8, 0xFBD8, self::INBEG);
        $this->pFormList[$this->cmUG('w')]        = new UG(0xFBDE, 0xFBDE, 0xFBDF, 0xFBDF, self::INBEG);
        $this->pFormList[$this->cmUG('i')]        = new UG(0xFEEF, 0xFBE8, 0xFBE9, 0xFEF0, self::NOBEG);
        $this->pFormList[$this->cmUG('y')]        = new UG(0xFEF1, 0xFEF3, 0xFEF4, 0xFEF2, self::NOBEG);
        
        $this->pFormList[self::HAMZA - self::BPAD]                = new UG(0xFE8B, 0xFE8B, 0xFE8C, 0xFB8C, self::NOBEG);
        $this->pFormList[$this->cmList[self::COLo] - self::BPAD]  = new UG(0xFBD9, 0xFBD9, 0xFBDA, 0xFBDA, self::INBEG);
        $this->pFormList[$this->cmList[self::COLu] - self::BPAD]  = new UG(0xFBDB, 0xFBDB, 0xFBDC, 0xFBDC, self::INBEG);
        $this->pFormList[$this->cmList[self::PRIMe] - self::BPAD] = new UG(0xFBE4, 0xFBE6, 0xFBE7, 0xFBE5, self::NOBEG);
        
        for ($i = 0; $i < self::EMAX - self::EPAD; $i++) {
            $this->pfToBasic[$i] = [];
        }
        
        
        // initialize presentation form to basic region mapping
        for ($i = 0; $i < count($this->pFormList); $i++) {
            if ($lig = $this->pFormList[$i]) {
                $this->pfToBasic[$lig->iForm - self::EPAD][0] = $i + self::BPAD;
                $this->pfToBasic[$lig->bForm - self::EPAD][0] = $i + self::BPAD;
                $this->pfToBasic[$lig->mForm - self::EPAD][0] = $i + self::BPAD;
                $this->pfToBasic[$lig->eForm - self::EPAD][0] = $i + self::BPAD;
            }
        }
        
        // the letter 'h' has some other mappings
        $this->pfToBasic[0xFEEB - self::EPAD][0] = $this->cmList[$this->getAsciiCodeAt('h')];
        $this->pfToBasic[0xFEEC - self::EPAD][0] = $this->cmList[$this->getAsciiCodeAt('h')];
        
        // joint letter LA and _LA
        $this->pfToBasic[0xFEFB - self::EPAD][0] = $this->cmList[$this->getAsciiCodeAt('l')];
        $this->pfToBasic[0xFEFB - self::EPAD][1] = $this->cmList[$this->getAsciiCodeAt('a')];
        $this->pfToBasic[0xFEFC - self::EPAD][0] = $this->cmList[$this->getAsciiCodeAt('l')];
        $this->pfToBasic[0xFEFC - self::EPAD][1] = $this->cmList[$this->getAsciiCodeAt('a')];
        
        // joint letter AA, AE, EE, II, OO, OE, UU, UE
        // AA, _AA
        $this->pfToBasic[0xFBEA - self::EPAD][0] = self::HAMZA;
        $this->pfToBasic[0xFBEA - self::EPAD][1] = $this->cmList[$this->getAsciiCodeAt('a')];
        $this->pfToBasic[0xFBEB - self::EPAD][0] = self::HAMZA;
        $this->pfToBasic[0xFBEB - self::EPAD][1] = $this->cmList[$this->getAsciiCodeAt('a')];
        
        // AE, _AE
        $this->pfToBasic[0xFBEC - self::EPAD][0] = self::HAMZA;
        $this->pfToBasic[0xFBEC - self::EPAD][1] = $this->cmList[$this->getAsciiCodeAt('e')];
        $this->pfToBasic[0xFBED - self::EPAD][0] = self::HAMZA;
        $this->pfToBasic[0xFBED - self::EPAD][1] = $this->cmList[$this->getAsciiCodeAt('e')];
        
        // EE, _EE, _EE_
        $this->pfToBasic[0xFBF6 - self::EPAD][0] = self::HAMZA;
        $this->pfToBasic[0xFBF6 - self::EPAD][1] = $this->cmList[self::PRIMe];
        $this->pfToBasic[0xFBF7 - self::EPAD][0] = self::HAMZA;
        $this->pfToBasic[0xFBF7 - self::EPAD][1] = $this->cmList[self::PRIMe];
        $this->pfToBasic[0xFBF8 - self::EPAD][0] = self::HAMZA;
        $this->pfToBasic[0xFBF8 - self::EPAD][1] = $this->cmList[self::PRIMe];
        $this->pfToBasic[0xFBD1 - self::EPAD][0] = self::HAMZA;
        $this->pfToBasic[0xFBD1 - self::EPAD][1] = $this->cmList[self::PRIMe];
        
        // II, _II, _II_
        $this->pfToBasic[0xFBF9 - self::EPAD][0] = self::HAMZA;
        $this->pfToBasic[0xFBF9 - self::EPAD][1] = $this->cmList[$this->getAsciiCodeAt('i')];
        $this->pfToBasic[0xFBFA - self::EPAD][0] = self::HAMZA;
        $this->pfToBasic[0xFBFA - self::EPAD][1] = $this->cmList[$this->getAsciiCodeAt('i')];
        $this->pfToBasic[0xFBFB - self::EPAD][0] = self::HAMZA;
        $this->pfToBasic[0xFBFB - self::EPAD][1] = $this->cmList[$this->getAsciiCodeAt('i')];
        
        // OO, _OO
        $this->pfToBasic[0xFBEE - self::EPAD][0] = self::HAMZA;
        $this->pfToBasic[0xFBEE - self::EPAD][1] = $this->cmList[$this->getAsciiCodeAt('o')];
        $this->pfToBasic[0xFBEF - self::EPAD][0] = self::HAMZA;
        $this->pfToBasic[0xFBEF - self::EPAD][1] = $this->cmList[$this->getAsciiCodeAt('o')];
        
        // OE, _OE
        $this->pfToBasic[0xFBF2 - self::EPAD][0] = self::HAMZA;
        $this->pfToBasic[0xFBF2 - self::EPAD][1] = $this->cmList[self::COLo];
        $this->pfToBasic[0xFBF3 - self::EPAD][0] = self::HAMZA;
        $this->pfToBasic[0xFBF3 - self::EPAD][1] = $this->cmList[self::COLo];
        
        // UU, _UU
        $this->pfToBasic[0xFBF0 - self::EPAD][0] = self::HAMZA;
        $this->pfToBasic[0xFBF0 - self::EPAD][1] = $this->cmList[$this->getAsciiCodeAt('u')];
        $this->pfToBasic[0xFBF1 - self::EPAD][0] = self::HAMZA;
        $this->pfToBasic[0xFBF1 - self::EPAD][1] = $this->cmList[$this->getAsciiCodeAt('u')];
        
        // UE, _UE
        $this->pfToBasic[0xFBF4 - self::EPAD][0] = self::HAMZA;
        $this->pfToBasic[0xFBF4 - self::EPAD][1] = $this->cmList[self::COLu];
        $this->pfToBasic[0xFBF5 - self::EPAD][0] = self::HAMZA;
        $this->pfToBasic[0xFBF5 - self::EPAD][1] = $this->cmList[self::COLu];
    }
    
    
    protected function cmUG($x)
    {
        return $this->cmList[$this->getAsciiCodeAt($x)] - self::BPAD;
    }
    
    
    /**
     * 将ascii编码转换成字符
     * @param $code
     * @return string
     */
    protected function asciiToString($code)
    {
        if ($code < 128) {
            $utf = chr($code);
        } else if ($code < 2048) {
            $utf = chr(192 + (($code - ($code % 64)) / 64));
            $utf .= chr(128 + ($code % 64));
        } else {
            $utf = chr(224 + (($code - ($code % 4096)) / 4096));
            $utf .= chr(128 + ((($code % 4096) - ($code % 64)) / 64));
            $utf .= chr(128 + ($code % 64));
        }
        
        return $utf;
    }
    
    
    /**
     * 取某个字符转换为ascii编码
     * @param string $string 字符串
     * @param int    $index 第几个字符
     * @return int
     */
    protected function getAsciiCodeAt($string, $index = 0)
    {
        $string = is_string($string) ? $string : '';
        $char   = mb_substr($string, $index, 1, 'utf-8');
        if (strlen($char) == 1) {
            return ord($char);
        }
        
        $char = mb_convert_encoding($char, 'UCS-4BE', 'UTF-8');
        $tmp  = unpack('N', $char);
        
        return $tmp[1];
    }
}
