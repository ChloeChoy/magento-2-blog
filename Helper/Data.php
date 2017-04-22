<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_Blog
 * @copyright   Copyright (c) 2016 Mageplaza (http://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */
namespace Mageplaza\Blog\Helper;

use Mageplaza\Core\Helper\AbstractData as CoreHelper;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\Helper\Context;
use Mageplaza\Blog\Model\PostFactory;
use Mageplaza\Blog\Model\CategoryFactory;
use Mageplaza\Blog\Model\TagFactory;
use Mageplaza\Blog\Model\TopicFactory;
use Mageplaza\Blog\Model\AuthorFactory;
use Magento\Framework\View\Element\Template\Context as TemplateContext;

class Data extends CoreHelper
{
    const XML_PATH_BLOG = 'blog/';
    const POST_IMG = 'mageplaza/blog/post/image';

    const SEARCH_DATA_TYPE = ['Post', 'Tag', 'Category'];
    const DEFAULT_URL_PREFIX = 'blog';

    public $postfactory;
	public $categoryfactory;
	public $tagfactory;
	public $topicfactory;
	public $store;
	public $modelTraffic;
	public $authorfactory;
	public $translitUrl;
	public $dateTime;

    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        PostFactory $postFactory,
        CategoryFactory $categoryFactory,
        TagFactory $tagFactory,
        TopicFactory $topicFactory,
		AuthorFactory $authorFactory,
        TemplateContext $templateContext,
		\Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
		\Magento\Framework\Filter\TranslitUrl $translitUrl,
		\Mageplaza\Blog\Model\Traffic $traffic
    ) {
    
        $this->postfactory     = $postFactory;
        $this->categoryfactory = $categoryFactory;
        $this->tagfactory      = $tagFactory;
        $this->topicfactory    = $topicFactory;
		$this->authorfactory    = $authorFactory;
		$this->dateTime   = $dateTime;
        $this->store = $templateContext->getStoreManager();
        $this->modelTraffic = $traffic;
        $this->translitUrl = $translitUrl;
        parent::__construct($context, $objectManager, $templateContext->getStoreManager());
    }

    /**
     * Is enable module on frontend
     *
     * @param null $store
     * @return bool
     */
    public function isEnabled($store = null)
    {
        $isModuleOutputEnabled = $this->isModuleOutputEnabled();

        return $isModuleOutputEnabled && $this->getBlogConfig('general/enabled', $store);
    }

    public function getBlogConfig($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_BLOG . $code, $storeId);
    }

	/**
	 * get sidebar config
	 */
	public function getSidebarConfig($code, $storeId = null)
	{
		return $this->getBlogConfig('sidebar/'.$code, $storeId);
	}

	/**
	 * @param $code, $storeId = null
	 * @return mixed
	 */
	public function getSeoConfig($code, $storeId = null)
	{
		return $this->getBlogConfig('seo/'.$code, $storeId);
	}

	public function getSelectedPostByMonth($type = null)
	{
		$month = $this->_getRequest()->getParam('month');
		return $list = ($month) ? $type->getSelectedPostsCollection()->addFieldToFilter('created_at',['like'=>$month . '%']) : $type->getSelectedPostsCollection();
	}

    public function getPostList($type = null, $id = null)
    {
//		$month = $this->_getRequest()->getParam('month');
        $list          = '';
        $posts         = $this->postfactory->create();
        $categoryModel = $this->categoryfactory->create();
        $tagModel      = $this->tagfactory->create();
        $topicModel    = $this->topicfactory->create();
        if ($type == null) {
//			$list = ($month) ? $posts->getCollection()->addFieldToFilter('created_at',['like'=>$month . '%']) : $posts->getCollection();
			$list = $posts->getCollection();
        } elseif ($type == 'category') {
            $category = $categoryModel->load($id);
//			$list = $this->getSelectedPostByMonth($category);
			$list     = $category->getSelectedPostsCollection();
        } elseif ($type == 'tag') {
            $tag  = $tagModel->load($id);
//			$list = $this->getSelectedPostByMonth($tag);
			$list = $tag->getSelectedPostsCollection();
        } elseif ($type == 'topic') {
            $topic = $topicModel->load($id);
//			$list = $this->getSelectedPostByMonth($topic);
			$list  = $topic->getSelectedPostsCollection();
        } elseif ($type == 'author') {
			$list = $posts->getCollection()->addFieldToFilter('author_id',$id);
		} elseif ($type == 'month') {
			$list = $posts->getCollection()->addFieldToFilter('created_at',['like'=>$id . '%']);
		}

        if ($list->getSize()) {
            $list->setOrder('created_at', 'desc')
                ->addFieldToFilter('enabled', 1);

			$results = $this->filterItems($list);
            return $results ? $results : '';
        }

        return '';
    }

    public function getCategoryList()
    {
        $category = $this->categoryfactory->create();
        $list     = $category->getCollection()->addFieldToFilter('enabled', 1);
        $result = $this->filterItems($list);
        if ($result == '') {
            return '';
        }
        return $result;
    }

    public function getTagList()
    {
        $tag  = $this->tagfactory->create();
        $list = $tag->getCollection()
            ->addFieldToFilter('enabled', 1);
        $result = $this->filterItems($list);
        if ($result == '') {
            return '';
        }
        return $result;
    }
	public function getTopicList()
	{
		$topic  = $this->topicfactory->create();
		$list = $topic->getCollection()
			->addFieldToFilter('enabled', 1);
		$result = $this->filterItems($list);
		if ($result == '') {
			return '';
		}
		return $result;
	}
    public function getCategoryCollection($array)
    {
        $category = $this->categoryfactory->create();
        $list     = $category->getCollection()
            ->addFieldToFilter('enabled', 1)
            ->addFieldToFilter('category_id', ['in' => $array]);
        $result = $this->filterItems($list);
        if ($result == '') {
            return '';
        }
        return $result;
    }

    public function getUrlByPost($post)
    {
        if ($post->getUrlKey()) {
            $url_prefix = $this->getBlogConfig('general/url_prefix') ?: self::DEFAULT_URL_PREFIX;
            $url_suffix = $this->getBlogConfig('general/url_suffix');

            $urlKey = '';
            if ($url_prefix) {
                $urlKey .= $url_prefix . '/post/';
            }
            $urlKey .= $post->getUrlKey();
            if ($url_suffix) {
                $urlKey .= $url_suffix;
            }
        }

        return $this->_getUrl($urlKey);
    }
	public function getAuthorByPost($authorId)
	{
		$author = $this->authorfactory->create();
		$list = $author->load($authorId);
		return $list;
	}
    public function getBlogUrl($code)
    {
    	$blogUrl = $this->getBlogConfig('general/url_prefix') ?: self::DEFAULT_URL_PREFIX;
        return $this->_getUrl($blogUrl . '/' . $code);
    }

    public function getPostByUrl($url)
    {
        $url   = $this->checkSuffix($url);
        $posts = $this->postfactory->create()->load($url, 'url_key');
        return $posts;
    }

    public function checkSuffix($url)
    {
        $url_suffix = $this->getBlogConfig('general/url_suffix');
        if (strpos($url, $url_suffix) !== false) {
            $url = str_replace($url_suffix, '', $url);
        }

        return $url;
    }

    public function getPostsByTag()
    {
        $posts      = $this->postfactory->create();
        $collection = $posts->getCollection()->addFieldToFilter('enabled', 1);
        $result = $this->filterItems($collection);
        if ($result == '') {
            return '';
        }
        return $result;
    }

    public function getPostsByCategory()
    {
        $collection = true;

        return $collection;
    }

    public function getImageUrl($image)
    {
        return $this->getBaseMediaUrl(). self::POST_IMG . $image;
    }

    public function getBaseMediaUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    public function getCategoryUrl($category)
    {
        return $this->_getUrl($this->getBlogConfig('general/url_prefix') . '/category/' . $category->getUrlKey());
    }

    public function getTagUrl($tag)
    {
        return $this->_getUrl($this->getBlogConfig('general/url_prefix') . '/tag/' . $tag->getUrlKey());
    }

	public function getAuthorUrl($author)
	{
		return $this->_getUrl($this->getBlogConfig('general/url_prefix') . '/author/' . $author->getUrlKey());
	}

    public function getTopicUrl($topic)
    {
        return $this->_getUrl($this->getBlogConfig('general/url_prefix') . '/topic/' . $topic->getUrlKey());
    }
	public function getMonthlyUrl($month)
	{
		return $this->_getUrl($this->getBlogConfig('general/url_prefix') . '/month/' . $month);
	}
    public function getPostCategoryHtml($post)
    {
        $categories = $this->getCategoryCollection($post->getCategoryIds());
        $categoryHtml = [];
        if (empty($categories)) {
            return null;
        } else {
            foreach ($categories as $_cat) {
                $categoryHtml[] = '<a class="mp-info" href="' . $this->getCategoryUrl($_cat) . '">' . $_cat->getName()
					. '</a>';
            }
        }
        $result = implode(', ', $categoryHtml);

        return $result;
    }

    public function getPost($id)
    {
        $post = $this->postfactory->create()->load($id);
        return $post;
    }
    public function getCategoryByParam($code, $param)
    {
        if ($code == 'id') {
            return $this->categoryfactory->create()->load($param);
        } else {
            return $this->categoryfactory->create()->load($param, $code);
        }
    }
    public function getTagByParam($code, $param)
    {
        if ($code == 'id') {
            return $this->tagfactory->create()->load($param);
        } else {
            return $this->tagfactory->create()->load($param, $code);
        }
    }
	public function getAuthorByParam($code, $param)
	{
		if ($code == 'id') {
			return $this->authorfactory->create()->load($param);
		} else {
			return $this->authorfactory->create()->load($param, $code);
		}
	}
    public function getTopicByParam($code, $param)
    {
        if ($code == 'id') {
            return $this->topicfactory->create()->load($param);
        } else {
            return $this->topicfactory->create()->load($param, $code);
        }
    }
    public function getCategoryByPost($postId)
    {
        $post = $this->postfactory->create()->load($postId);
        return $post->getSelectedCategoriesCollection();
    }
    public function getTagsByPost($postId)
    {
        $post = $this->postfactory->create()->load($postId);
        return $post->getSelectedTagsCollection();
    }
    public function getTopicByPost($postId)
    {
        $post = $this->postfactory->create()->load($postId);
        return $post->getSelectedTopicsCollection();
    }

    /**
     * get most view post
     */
    public function getMosviewPosts()
    {
        $posts = $this->modelTraffic->getCollection()->addFieldToFilter('enabled', 1);
        $posts->join(
            'mageplaza_blog_post',
            'main_table.post_id=mageplaza_blog_post.post_id',
            '*'
        );
        $posts->setOrder('numbers_view', 'DESC');
        $postList = $this->filterPost($posts, $this->getBlogConfig('sidebar/number_mostview_posts'));
        if ($postList == '') {
            return '';
        }
        return $postList;
    }

    /**
     * get recent post
     */
    public function getRecentPost()
    {
        $posts = $this->postfactory->create()
            ->getCollection()
            ->addFieldToFilter('enabled', 1)
            ->setOrder('created_at', 'DESC');
        $postList = $this->filterPost($posts, $this->getBlogConfig('sidebar/number_recent_posts'));
        if ($postList == '') {
            return '';
        }
        return $postList;
    }

    /**
     * filter items by store
     */
    public function filterItems($items, $limit = null)
    {
        $storeId = $this->store->getStore()->getId();
        $count = 0;
        $results = array();
        foreach ($items as $item) {
        	$itemStoreIds = $item->getStoreIds();
			$itemStore = $itemStoreIds !== null ? explode(',', $itemStoreIds) : '';
			if (is_array($itemStore) && (in_array($storeId, $itemStore) || in_array('0', $itemStore))) {
				if ($limit && $count >= $limit) {
					break;
				}
				$count++;
				array_push($results, $item);
			}
        }

        if ($count == 0) {
            return '';
        }
		return $results;
    }

	public function filterPost($items, $limit )
	{
		$storeId = $this->store->getStore()->getId();
		$count = 0;
		$results = array();
		foreach ($items as $item) {
			$itemStoreIds = $item->getStoreIds();
			$itemStore = $itemStoreIds !== null ? explode(',', $itemStoreIds) : '';
			if (is_array($itemStore) && (in_array($storeId, $itemStore) || in_array('0', $itemStore))) {
				if ($limit && $count >= $limit) {
					break;
				}
				$count++;
				array_push($results, $item);
			}
		}

		if ($count == 0 || $limit == 0) {
			return '';
		}
		return $results;
	}
    /**
	 * get search blog's data
	 */
    public function getSearchBlogData()
	{
		$result = [];
		$posts = $this->getPostList();
		$categories = $this->getCategoryList();
		$tags = $this->getTagList();

		$postsData = $this->getSearchItemsData($posts, self::SEARCH_DATA_TYPE[0]);
		$tagsData = $this->getSearchItemsData($tags, self::SEARCH_DATA_TYPE[1]);
		$categoriesData = $this->getSearchItemsData($categories, self::SEARCH_DATA_TYPE[2]);

		$result = array_merge($result, $postsData, $tagsData, $categoriesData);
		return json_encode($result);
	}

	/**
	 * get search items data
	 * @return array
	 */
	public function getSearchItemsData($items, $type)
	{
		$data = array();
		if ($items) {
			$limitDesc = $this->getSidebarConfig('search/description') ?: 100;
			foreach ($items as $item) {
				$tmp = array(
					'value' => $item->getName(),
					'url'	=> $type == self::SEARCH_DATA_TYPE[0] ? $this->getUrlByPost($item) :
						($type == self::SEARCH_DATA_TYPE[1] ? $this->getTagUrl($item) : $this->getCategoryUrl($item)),
					'image'	=> $type == self::SEARCH_DATA_TYPE[0] ? ($item->getImage() ? $this->getImageUrl($item->getImage()) : $this->getDefaultImageUrl()) : '',
					'desc'	=> $type == self::SEARCH_DATA_TYPE[0]
						? (substr($item->getShortDescription(),0, $limitDesc) ?: 'No description')
						: ($type == self::SEARCH_DATA_TYPE[1] ? (substr($item->getDescription(), 0, $limitDesc)
							?: 'No description') : '')
				);
				array_push($data, $tmp);
			}
		}

		return $data;
	}
	public function getDefaultImageUrl(){
		return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_STATIC).'frontend/Magento/luma/en_US/Mageplaza_Blog/media/images/Mageplaza-logo.png';
	}
	public function generateUrlKey($name, $count)
	{
		$name = $this->strReplace($name);
		$text = $this->translitUrl->filter($name);
		if ($count == 0) {
			$count = '';
		}
		if (empty($text)) {
			return 'n-a' . $count;
		}
		return $text . $count;
	}

	public function strReplace($str){

		$str = trim(mb_strtolower($str));
		$str = preg_replace('/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/', 'a', $str);
		$str = preg_replace('/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/', 'e', $str);
		$str = preg_replace('/(ì|í|ị|ỉ|ĩ)/', 'i', $str);
		$str = preg_replace('/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/', 'o', $str);
		$str = preg_replace('/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/', 'u', $str);
		$str = preg_replace('/(ỳ|ý|ỵ|ỷ|ỹ)/', 'y', $str);
		$str = preg_replace('/(đ)/', 'd', $str);
//			$str = preg_replace('/[^a-z0-9-\s]/', '', $str);
//			$str = preg_replace('/([\s]+)/', '-', $str);
		return $str;
	}
	public function getPostDate()
	{
		$posts = $this->getPostList();
		$postDates = array();
		if($posts) {
			foreach ($posts as $post) {
				$postDates[] = $post->getCreatedAt();
			}
		}
		return $postDates;
	}
	public function getDateLabel(){
		$posts = $this->getPostList();
		$postDates = array();

		if($posts) {
			foreach ($posts as $post) {
				$postDates[] = $post->getMonthlyCreatedAt();
			}
		}
		$result = array_values(array_unique($postDates));
		return $result;
	}
	public function getDateArray(){
		$dateArray = array();
		foreach ($this->getPostDate() as $postDate){
			$dateArray[] = date("F Y",$this->dateTime->timestamp($postDate));
		}

		return $dateArray;
	}
	public function getDateArrayCount()
	{
		return $dateArrayCount = array_values(array_count_values($this->getDateArray()));
	}
	public function getDateArrayUnique()
	{
		return $dateArrayUnique = array_values(array_unique($this->getDateArray()));
	}
	public function getDateCount()
	{
		$count=0;
		$limit = $this->getBlogConfig('monthly_archive/number_records');
		$dateArrayCount = $this->getDateArrayCount();
		foreach ($dateArrayCount as $dateCount){
			$count++;
		}
		$result = ($count < $limit) ? $count : $limit ;
		return $result;
	}
}
