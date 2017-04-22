<?php
/**
 * Created by PhpStorm.
 * User: HoangKuty
 * Date: 4/8/2017
 * Time: 2:23 PM
 */
namespace Mageplaza\Blog\Model\ResourceModel;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Author extends AbstractDb
{
	public $translitUrl;
	protected $_isPkAutoIncrement = false;

	public function __construct(
		\Magento\Framework\Filter\TranslitUrl $translitUrl,
		\Magento\Framework\Model\ResourceModel\Db\Context $context
	) {
		$this->translitUrl = $translitUrl;
		parent::__construct($context);
	}

	protected function _construct()
	{
		$this->_init('mageplaza_blog_author','user_id');
	}

	protected function _beforeSave(\Magento\Framework\Model\AbstractModel $object)
	{
		if ($object->isObjectNew()) {
			$count   = 0;
			$objName = $object->getDisplayName();
			if ($object->getUrlKey()) {
				$urlKey = $object->getUrlKey();
			} else {
				$urlKey = $this->generateUrlKey($objName, $count);
			}
			while ($this->checkUrlKey($urlKey)) {
				$count++;
				$urlKey = $this->generateUrlKey($urlKey, $count);
			}
			$object->setUrlKey($urlKey);
		} else {
			$objectId = $object->getId();
			$count    = 0;
			$objName  = $object->getDisplayName();
			if ($object->getUrlKey()) {
				$urlKey = $object->getUrlKey();
			} else {
				$urlKey = $this->generateUrlKey($objName, $count);
			}
			while ($this->checkUrlKey($urlKey, $objectId)) {
				$count++;
				$urlKey = $this->generateUrlKey($urlKey, $count);
			}

			$object->setUrlKey($urlKey);
		}
	}

	public function generateUrlKey($name, $count)
	{
		$text = $this->translitUrl->filter($name);
		if ($count == 0) {
			$count = '';
		}
		if (empty($text)) {
			return 'n-a' . $count;
		}
		return $text . $count;
	}

	public function checkUrlKey($url, $id = null)
	{
		$adapter = $this->getConnection();
		if ($id) {
			$select            = $adapter->select()
				->from($this->getMainTable(), '*')
				->where('url_key = :url_key')
				->where('user_id != :user_id');
			$binds['url_key']  = (string)$url;
			$binds ['user_id'] = (int)$id;
		} else {
			$select = $adapter->select()
				->from($this->getMainTable(), '*')
				->where('url_key = :url_key');
			$binds  = ['url_key' => (string)$url];
		}
		$result = $adapter->fetchOne($select, $binds);

		return $result;
	}
}