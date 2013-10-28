<?php
namespace Podlove\Modules\ImportExport;

class Importer {

	// path to import file
	private $file;

	// SimpleXML document of import file
	private $xml;

	public function __construct($file) {
		$this->file = $file;
	}

	public function import() {

		$this->xml = simplexml_load_file($this->file);
		$this->xml->registerXPathNamespace('wpe', \Podlove\Exporter::XML_NAMESPACE);

		// TODO: clean podlove tables beforehand?

		$this->importEpisodes();
	}

	private function importEpisodes()
	{
		global $wpdb;
		
		$episodes = $this->xml->xpath('//wpe:episode');
		foreach ($episodes as $episode) {
			$new_episode = new \Podlove\Model\Episode;

			foreach ($episode->children('wpe', true) as $attribute) {
				$value = (string) $attribute;
				$wpdb->escape_by_ref($value);
				$new_episode->{$attribute->getName()} = $value;
			}

			if ($new_post_id = $this->getNewPostId($new_episode->post_id)) {
				$new_episode->post_id = $new_post_id;
				$new_episode->save();
			} else {
				// no matching post found
			}
		}
	}

	/**
	 * Get mapping for post id after post import.
	 *
	 * When importing posts, their IDs might change.
	 * This function maps an existing post id to the new one.
	 * 
	 * @param  int      $old_post_id
	 * @return int|null post_id on success, otherwise null.
	 */
	private function getNewPostId($old_post_id)
	{
		$query_for_post_id = new \WP_Query(array(
			'post_type' => 'podcast',
			'meta_query' => array(
				array(
					'key' => 'import_id',
					'value' => $old_post_id,
					'compare' => '='
				)
			)
		));

		if ($query_for_post_id->have_posts()) {
			$p = $query_for_post_id->next_post();
			return $p->ID;
		} else {
			return null;
		}
	}

}