<?php
/**
 * FecShop file.
 *
 * @link http://www.fecshop.com/
 * @copyright Copyright (c) 2016 FecShop Software LLC
 * @license http://www.fecshop.com/license/
 */

namespace fecshop\app\apphtml5\modules\Catalog\block\reviewproduct;

use fecshop\app\apphtml5\modules\Catalog\helpers\Review as ReviewHelper;
use Yii;

/**
 * @author Terry Zhao <2358269014@qq.com>
 * @since 1.0
 */
class Add
{
    protected $_add_captcha;

    public function __construct()
    {
        ReviewHelper::initReviewConfig();
    }

    public function getAddCaptcha()
    {
        if (!$this->_add_captcha) {
            $reviewParam = Yii::$app->getModule('catalog')->params['review'];
            $this->_add_captcha = isset($reviewParam['add_captcha']) ? $reviewParam['add_captcha'] : false;
        }

        return $this->_add_captcha;
    }

    public function getLastData($editForm)
    {
        if (!is_array($editForm)) {
            $editForm = [];
        }
        $_id = Yii::$app->request->get('_id');
        if (!$_id) {
            Yii::$service->page->message->addError('product _id  is empty');

            return [];
        }
        $product = Yii::$service->product->getByPrimaryKey($_id);
        if (!$product['spu']) {
            Yii::$service->page->message->addError('product _id:'.$_id.'  is not exist in product collection');

            return [];
        }

        $price_info = $this->getProductPriceInfo($product);
        $spu = $product['spu'];
        $image = $product['image'];
        $main_img = isset($image['main']['image']) ? $image['main']['image'] : '';
        $url_key = $product['url_key'];
        $product_name = Yii::$service->store->getStoreAttrVal($product['name'], 'name');
        $customer_name = '';
        if (!Yii::$app->user->isGuest) {
            $identity = Yii::$app->user->identity;
            $customer_name = $identity['firstname'].' '.$identity['lastname'];
        }

        return [
            'customer_name'    => $customer_name,
            'product_id'    => $_id,
            'product_name'    => $product_name,
            'spu'            => $spu,
            'price_info'    => $price_info,
            'main_img'        => $main_img,
            'editForm'        => $editForm,
            'add_captcha'    => $this->getAddCaptcha(),
            'url'        => Yii::$service->url->getUrl($url_key),
        ];
    }

    public function saveReview($editForm)
    {
        $add_captcha = $this->getAddCaptcha();
        $product_id = isset($editForm['product_id']) ? $editForm['product_id'] : '';
        if (!$product_id) {
            Yii::$service->page->message->addError(['Product id can not empty']);

            return;
        }
        $rate_star = isset($editForm['rate_star']) ? $editForm['rate_star'] : '';
        if (!$rate_star) {
            Yii::$service->page->message->addError(['Rate Star can not empty']);

            return;
        }
        $name = isset($editForm['name']) ? $editForm['name'] : '';
        if (!$name) {
            Yii::$service->page->message->addError(['Your Name can not empty']);

            return;
        }
        $summary = isset($editForm['summary']) ? $editForm['summary'] : '';
        if (!$summary) {
            Yii::$service->page->message->addError(['Summary can not empty']);

            return;
        }
        $review_content = isset($editForm['review_content']) ? $editForm['review_content'] : '';
        if (!$review_content) {
            Yii::$service->page->message->addError(['Review content can not empty']);

            return;
        }
        // captcha validate
        $captcha = isset($editForm['captcha']) ? $editForm['captcha'] : '';
        if ($add_captcha && !$captcha) {
            Yii::$service->page->message->addError(['Captcha can not empty']);

            return;
        } elseif ($captcha && $add_captcha && !\Yii::$service->helper->captcha->validateCaptcha($captcha)) {
            Yii::$service->page->message->addError(['Captcha is not right']);

            return;
        }
        $product = Yii::$service->product->getByPrimaryKey($product_id);
        if (!$product['spu']) {
            Yii::$service->page->message->addError('product _id:'.$product_id.'  is not exist in product collection');

            return;
        }
        $editForm['spu'] = $product['spu'];
        $editForm['status'] = $product['spu'];
        Yii::$service->product->review->addReview($editForm);
        Yii::$service->page->message->addCorrect('Add product review success,Thank you! you can click product image to continue this product.');

        return true;
    }

    protected function getProductPriceInfo($product)
    {
        $price = $product['price'];
        $special_price = $product['special_price'];
        $special_from = $product['special_from'];
        $special_to = $product['special_to'];

        return Yii::$service->product->price->getCurrentCurrencyProductPriceInfo($price, $special_price, $special_from, $special_to);
    }

    protected function getSpuData()
    {
        $spu = $this->_product['spu'];
        $filter = [
            'select'    => ['size'],
            'where'            => [
                ['spu' => $spu],
            ],
            'asArray' => true,
        ];
        $coll = Yii::$service->product->coll($filter);
        if (is_array($coll['coll']) && !empty($coll['coll'])) {
            foreach ($coll['coll'] as $one) {
                $spu = $one['spu'];
            }
        }
    }
}
