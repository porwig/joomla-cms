<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Amazons3
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

/**
 * Defines the PUT operations on buckets
 *
 * @package     Joomla.Platform
 * @subpackage  Amazons3
 * @since       ??.?
 */
class JAmazons3OperationsBucketsPut extends JAmazons3OperationsBuckets
{
	/**
	 * Creates the request for creating a bucket and returns the response from Amazon
	 *
	 * @param   string  $bucket        The bucket name
	 * @param   string  $bucketRegion  The bucket region (default: US Standard)
	 * @param   string  $acl           An array containing the ACL permissions
	 *                                 (either canned or explicitly specified)
	 *
	 * @return string  The response body
	 *
	 * @since   ??.?
	 */
	public function putBucket($bucket, $bucketRegion = "", $acl = null)
	{
		$url = "https://" . $bucket . "." . $this->options->get("api.url") . "/";
		$headers = array(
			"Date" => date("D, d M Y H:i:s O"),
		);
		$content = "";

		// Check for ACL permissions
		if (is_array($acl))
		{
			// Check for canned ACL permission
			if (array_key_exists("acl", $acl))
			{
				$headers["x-amz-acl"] = $acl["acl"];
			}
			else
			{
				// Access permissions were specified explicitly
				foreach ($acl as $aclPermission => $aclGrantee)
				{
					$headers["x-amz-grant-" . $aclPermission] = $aclGrantee;
				}
			}
		}

		if ($bucketRegion != "")
		{
			$content = "<CreateBucketConfiguration xmlns=\"http://s3.amazonaws.com/doc/2006-03-01/\">"
				. "<LocationConstraint>" . $bucketRegion . "</LocationConstraint>"
				. "</CreateBucketConfiguration>";

			$headers["Content-type"] = "application/x-www-form-urlencoded; charset=utf-8";
			$headers["Content-Length"] = strlen($content);
		}

		$authorization = $this->createAuthorization("PUT", $url, $headers);
		$headers["Authorization"] = $authorization;
		unset($headers["Content-type"]);

		// Send the http request
		$response = $this->client->put($url, $content, $headers);

		// Process the response
		$response_body = $this->processResponse($response);

		return $response_body;
	}

	/**
	 * Creates the request for setting the permissions on an existing bucket
	 * using access control lists (ACL)
	 *
	 * @param   string  $bucket  The bucket name
	 * @param   string  $acl     An array containing the ACL permissions
	 *                           (either canned or explicitly specified)
	 *
	 * @return string  The response body
	 *
	 * @since   ??.?
	 */
	public function putBucketAcl($bucket, $acl = null)
	{
		$url = "https://" . $bucket . "." . $this->options->get("api.url") . "/?acl";
		$headers = array(
			"Date" => date("D, d M Y H:i:s O"),
		);

		// Check for ACL permissions
		if (is_array($acl))
		{
			// Check for canned ACL permission
			if (array_key_exists("acl", $acl))
			{
				$headers["x-amz-acl"] = $acl["acl"];
			}
			else
			{
				// Access permissions were specified explicitly
				foreach ($acl as $aclPermission => $aclGrantee)
				{
					$headers["x-amz-grant-" . $aclPermission] = $aclGrantee;
				}
			}
		}

		$authorization = $this->createAuthorization("PUT", $url, $headers);
		$headers["Authorization"] = $authorization;

		// Send the http request
		$response = $this->client->put($url, "", $headers);

		// Process the response
		$response_body = $this->processResponse($response);

		return $response_body;
	}
}
