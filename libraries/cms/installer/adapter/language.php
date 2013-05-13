<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  Installer
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

jimport('joomla.filesystem.folder');

/**
 * Language installation adapter
 *
 * @package     Joomla.Libraries
 * @subpackage  Installer
 * @since       3.1
 */
class JInstallerAdapterLanguage extends JInstallerAdapter
{
	/**
	 * Get the filtered extension element from the manifest
	 *
	 * @return  string  The filtered element
	 *
	 * @since   3.1
	 */
	public function getElement($element = null)
	{
		if (!$element)
		{
			$element = (string) $this->manifest->tag;
		}

		return $element;
	}

	/**
	 * Custom install method
	 *
	 * Note: This behaves badly due to hacks made in the middle of 1.5.x to add
	 * the ability to install multiple distinct packs in one install. The
	 * preferred method is to use a package to install multiple language packs.
	 *
	 * @return  boolean  True on success
	 *
	 * @since   3.1
	 */
	public function install()
	{
		$source = $this->parent->getPath('source');

		if (!$source)
		{
			$this->parent
				->setPath(
				'source',
				($this->parent->extension->client_id ? JPATH_ADMINISTRATOR : JPATH_SITE) . '/language/' . $this->parent->extension->element
			);
		}

		// Get the client application target
		$cname = (string) $this->manifest->attributes()->client;

		if ($cname)
		{
			// Attempt to map the client to a base path
			$client = JApplicationHelper::getClientInfo($cname, true);

			if ($client === null)
			{
				throw new RuntimeException(JText::sprintf('JLIB_INSTALLER_ABORT', JText::sprintf('JLIB_INSTALLER_ERROR_UNKNOWN_CLIENT_TYPE', $cname)));
			}
			$basePath = $client->path;
			$clientId = $client->id;
			$element = $this->manifest->files;

			return $this->_install('', $basePath, $clientId, $element);
		}
		else
		{
			// No client attribute was found so we assume the site as the client
			$basePath = JPATH_SITE;
			$clientId = 0;
			$element = $this->manifest->files;

			return $this->_install('', $basePath, $clientId, $element);
		}
	}

	/**
	 * Install function that is designed to handle individual clients
	 *
	 * @param   string   $cname     Cname @deprecated 4.0
	 * @param   string   $basePath  The base name.
	 * @param   integer  $clientId  The client id.
	 * @param   object   &$element  The XML element.
	 *
	 * @return  boolean
	 *
	 * @since   3.1
	 */
	protected function _install($cname, $basePath, $clientId, &$element)
	{
		// Get the Language tag [ISO tag, eg. en-GB]
		$tag = (string) $this->manifest->tag;

		// Check if we found the tag - if we didn't, we may be trying to install from an older language package
		if (!$tag)
		{
			throw new RuntimeException(JText::sprintf('JLIB_INSTALLER_ABORT', JText::_('JLIB_INSTALLER_ERROR_NO_LANGUAGE_TAG')));
		}

		$this->tag = $tag;

		// Set the language installation path
		$this->parent->setPath('extension_site', $basePath . '/language/' . $tag);

		// If the language directory does not exist, let's create it
		$created = false;

		if (!file_exists($this->parent->getPath('extension_site')))
		{
			if (!$created = JFolder::create($this->parent->getPath('extension_site')))
			{
				throw new RuntimeException(
					JText::sprintf(
						'JLIB_INSTALLER_ABORT',
						JText::sprintf('JLIB_INSTALLER_ERROR_CREATE_FOLDER_FAILED', $this->parent->getPath('extension_site'))
					)
				);
			}
		}
		else
		{
			// Look for an update function or update tag
			$updateElement = $this->manifest->update;

			// Upgrade manually set or update tag detected
			if ($this->parent->isUpgrade() || $updateElement)
			{
				// Transfer control to the update function
				return $this->update();
			}
			elseif (!$this->parent->isOverwrite())
			{
				// We didn't have overwrite set, find an update function or find an update tag so lets call it safe
				if (file_exists($this->parent->getPath('extension_site')))
				{
					// If the site exists say so.
					throw new RuntimeException(
						JText::sprintf('JLIB_INSTALLER_ABORT', JText::sprintf('JLIB_INSTALLER_ERROR_FOLDER_IN_USE', $this->parent->getPath('extension_site')))
					);
				}
				else
				{
					// If the admin exists say so.
					throw new RuntimeException(
						JText::sprintf('JLIB_INSTALLER_ABORT', JText::sprintf('JLIB_INSTALLER_ERROR_FOLDER_IN_USE', $this->parent->getPath('extension_administrator')))
					);
				}
			}
		}

		/*
		 * If we created the language directory we will want to remove it if we
		 * have to roll back the installation, so let's add it to the installation
		 * step stack
		 */
		if ($created)
		{
			$this->parent->pushStep(array('type' => 'folder', 'path' => $this->parent->getPath('extension_site')));
		}

		// Copy all the necessary files
		if ($this->parent->parseFiles($element) === false)
		{
			// TODO: throw exception
			return false;
		}

		// Parse optional tags
		$this->parent->parseMedia($this->manifest->media);

		// Copy all the necessary font files to the common pdf_fonts directory
		$this->parent->setPath('extension_site', $basePath . '/language/pdf_fonts');
		$overwrite = $this->parent->setOverwrite(true);

		if ($this->parent->parseFiles($this->manifest->fonts) === false)
		{
			// TODO: throw exception
			return false;
		}
		$this->parent->setOverwrite($overwrite);

		// Get the language description
		$description = (string) $this->manifest->description;

		if ($description)
		{
			$this->parent->message = JText::_($description);
		}
		else
		{
			$this->parent->message = '';
		}

		// Add an entry to the extension table with a whole heap of defaults
		$this->extension->name = $this->name;
		$this->extension->type = 'language';
		$this->extension->element = $this->tag;

		// There is no folder for languages
		$this->extension->folder = '';
		$this->extension->enabled = 1;
		$this->extension->protected = 0;
		$this->extension->access = 0;
		$this->extension->client_id = $clientId;
		$this->extension->params = $this->parent->getParams();
		$this->extension->manifest_cache = $this->parent->generateManifestCache();

		if (!$this->extension->store())
		{
			// Install failed, roll back changes
			throw new RuntimeException(JText::sprintf('JLIB_INSTALLER_ABORT', $this->extension->getError()));
		}

		// Clobber any possible pending updates
		$update = JTable::getInstance('update');
		$uid = $update->find(array('element' => $this->tag, 'type' => 'language', 'client_id' => '', 'folder' => ''));

		if ($uid)
		{
			$update->delete($uid);
		}

		return $this->extension->extension_id;
	}

	/**
	 * Custom update method
	 *
	 * @return  boolean  True on success, false on failure
	 *
	 * @since   3.1
	 */
	public function update()
	{
		$cname = $this->manifest->attributes()->client;

		// Attempt to map the client to a base path
		$client = JApplicationHelper::getClientInfo($cname, true);

		if ($client === null || (empty($cname) && $cname !== 0))
		{
			throw new RuntimeException(JText::sprintf('JLIB_INSTALLER_ABORT', JText::sprintf('JLIB_INSTALLER_ERROR_UNKNOWN_CLIENT_TYPE', $cname)));
		}
		$basePath = $client->path;
		$clientId = $client->id;

		// Get the language name
		// Set the extensions name
		$this->getName();

		// Get the Language tag [ISO tag, eg. en-GB]
		$tag = (string) $this->manifest->tag;

		// Check if we found the tag - if we didn't, we may be trying to install from an older language package
		if (!$tag)
		{
			throw new RuntimeException(JText::sprintf('JLIB_INSTALLER_ABORT', JText::_('JLIB_INSTALLER_ERROR_NO_LANGUAGE_TAG')));
		}

		$this->tag = $tag;

		// Set the language installation path
		$this->parent->setPath('extension_site', $basePath . '/language/' . $this->tag);

		// Copy all the necessary files
		if ($this->parent->parseFiles($this->manifest->files) === false)
		{
			// TODO: throw exception
			return false;
		}

		// Parse optional tags
		$this->parent->parseMedia($this->manifest->media);

		// Copy all the necessary font files to the common pdf_fonts directory
		$this->parent->setPath('extension_site', $basePath . '/language/pdf_fonts');
		$overwrite = $this->parent->setOverwrite(true);

		if ($this->parent->parseFiles($this->manifest->fonts) === false)
		{
			// TODO: throw exception
			return false;
		}
		$this->parent->setOverwrite($overwrite);

		// Get the language description and set it as message
		$this->parent->message = (string) $this->manifest->description;

		/**
		 * ---------------------------------------------------------------------------------------------
		 * Finalization and Cleanup Section
		 * ---------------------------------------------------------------------------------------------
		 */

		// Clobber any possible pending updates
		$update = JTable::getInstance('update');
		$uid = $update->find(array('element' => $this->tag, 'type' => 'language', 'client_id' => $clientId));

		if ($uid)
		{
			$update->delete($uid);
		}

		// Update an entry to the extension table
		$eid = $this->extension->find(array('element' => strtolower($this->tag), 'type' => 'language', 'client_id' => $clientId));

		if ($eid)
		{
			$this->extension->load($eid);
		}
		else
		{
			// Set the defaults
			// There is no folder for language
			$this->extension->folder = '';
			$this->extension->enabled = 1;
			$this->extension->protected = 0;
			$this->extension->access = 0;
			$this->extension->client_id = $clientId;
			$this->extension->params = $this->parent->getParams();
		}
		$this->extension->name = $this->name;
		$this->extension->type = 'language';
		$this->extension->element = $this->tag;
		$this->extension->manifest_cache = $this->parent->generateManifestCache();

		if (!$this->extension->store())
		{
			// Install failed, roll back changes
			throw new RuntimeException(JText::sprintf('JLIB_INSTALLER_ABORT', $this->extension->getError()));
		}

		return $this->extension->extension_id;
	}

	/**
	 * Method to prepare the uninstall script
	 *
	 * This method populates the $this->extension object, checks whether the extension is protected,
	 * and sets the extension paths
	 *
	 * @param   integer  $id  The extension ID to load
	 *
	 * @return  boolean  True on success
	 *
	 * @since   3.1
	 */
	protected function setupUninstall($id)
	{
		// Run the common parent methods
		if (parent::setupUninstall($id))
		{
			// Grab a copy of the client details
			$client = JApplicationHelper::getClientInfo($this->extension->client_id);

			// Check the element isn't blank to prevent nuking the languages directory, just in case
			$element = $this->extension->element;

			if (empty($element))
			{
				JLog::add(JText::_('JLIB_INSTALLER_ERROR_LANG_UNINSTALL_ELEMENT_EMPTY'), JLog::WARNING, 'jerror');

				return false;
			}

			// Verify that it's not the default language for that client
			$params = JComponentHelper::getParams('com_languages');

			if ($params->get($client->name) == $element)
			{
				JLog::add(JText::_('JLIB_INSTALLER_ERROR_LANG_UNINSTALL_DEFAULT'), JLog::WARNING, 'jerror');

				return false;
			}

			// Construct the path from the client, the language and the extension element name
			$path = $client->path . '/language/' . $element;

			// Get the package manifest object and remove media
			$this->parent->setPath('source', $path);
		}

		return true;
	}

	/**
	 * Custom uninstall method
	 *
	 * @param   string  $eid  The tag of the language to uninstall
	 *
	 * @return  mixed  Return value for uninstall method in component uninstall file
	 *
	 * @since   3.1
	 */
	public function uninstall($eid)
	{
		// Prepare the uninstaller for action
		$this->setupUninstall((int) $eid);

		// Get the source path as set in the setup method
		$path = $this->parent->getPath('source');

		// We do findManifest to avoid problem when uninstalling a list of extension: getManifest cache its manifest file
		$this->parent->findManifest();
		$this->manifest = $this->parent->getManifest();
		$this->parent->removeFiles($this->manifest->media);

		// Check it exists
		if (!JFolder::exists($path))
		{
			// If the folder doesn't exist lets just nuke the row as well and presume the user killed it for us
			$this->extension->delete();
			JLog::add(JText::_('JLIB_INSTALLER_ERROR_LANG_UNINSTALL_PATH_EMPTY'), JLog::WARNING, 'jerror');

			return false;
		}

		if (!JFolder::delete($path))
		{
			// If deleting failed we'll leave the extension entry in tact just in case
			JLog::add(JText::_('JLIB_INSTALLER_ERROR_LANG_UNINSTALL_DIRECTORY'), JLog::WARNING, 'jerror');

			return false;
		}

		// Remove the extension table entry
		$this->extension->delete();

		// Setting the language of users which have this language as the default language
		$query = $this->db->getQuery(true)
			->from('#__users')
			->select('*');
		$this->db->setQuery($query);
		$users = $this->db->loadObjectList();

		// Grab a copy of the client details
		$client = JApplicationHelper::getClientInfo($this->extension->client_id);

		if ($client->name == 'administrator')
		{
			$param_name = 'admin_language';
		}
		else
		{
			$param_name = 'language';
		}

		$count = 0;

		foreach ($users as $user)
		{
			$registry = new JRegistry;
			$registry->loadString($user->params);

			if ($registry->get($param_name) == $this->element)
			{
				$registry->set($param_name, '');
				$query = $this->db->getQuery(true)
					->update('#__users')
					->set('params=' . $this->db->quote($registry))
					->where('id=' . (int) $user->id);
				$this->db->setQuery($query);
				$this->db->execute();
				$count++;
			}
		}
		if (!empty($count))
		{
			JLog::add(JText::plural('JLIB_INSTALLER_NOTICE_LANG_RESET_USERS', $count), JLog::NOTICE, 'jerror');
		}

		// All done!
		return true;
	}

	/**
	 * Custom discover method
	 * Finds language files
	 *
	 * @return  boolean  True on success
	 *
	 * @since  3.1
	 */
	public function discover()
	{
		$results = array();
		$site_languages = JFolder::folders(JPATH_SITE . '/language');
		$admin_languages = JFolder::folders(JPATH_ADMINISTRATOR . '/language');

		foreach ($site_languages as $language)
		{
			if (file_exists(JPATH_SITE . '/language/' . $language . '/' . $language . '.xml'))
			{
				$manifest_details = JInstaller::parseXMLInstallFile(JPATH_SITE . '/language/' . $language . '/' . $language . '.xml');
				$extension = JTable::getInstance('extension');
				$extension->type = 'language';
				$extension->client_id = 0;
				$extension->element = $language;
				$extension->folder = '';
				$extension->name = $language;
				$extension->state = -1;
				$extension->manifest_cache = json_encode($manifest_details);
				$extension->params = '{}';
				$results[] = $extension;
			}
		}
		foreach ($admin_languages as $language)
		{
			if (file_exists(JPATH_ADMINISTRATOR . '/language/' . $language . '/' . $language . '.xml'))
			{
				$manifest_details = JInstaller::parseXMLInstallFile(JPATH_ADMINISTRATOR . '/language/' . $language . '/' . $language . '.xml');
				$extension = JTable::getInstance('extension');
				$extension->type = 'language';
				$extension->client_id = 1;
				$extension->element = $language;
				$extension->folder = '';
				$extension->name = $language;
				$extension->state = -1;
				$extension->manifest_cache = json_encode($manifest_details);
				$extension->params = '{}';
				$results[] = $extension;
			}
		}
		return $results;
	}

	/**
	 * Custom discover install method
	 * Basically updates the manifest cache and leaves everything alone
	 *
	 * @return  integer  The extension id
	 *
	 * @since   3.1
	 */
	public function discover_install()
	{
		// Need to find to find where the XML file is since we don't store this normally
		$client = JApplicationHelper::getClientInfo($this->parent->extension->client_id);
		$short_element = $this->parent->extension->element;
		$manifestPath = $client->path . '/language/' . $short_element . '/' . $short_element . '.xml';
		$this->parent->manifest = $this->parent->isManifest($manifestPath);
		$this->parent->setPath('manifest', $manifestPath);
		$this->parent->setPath('source', $client->path . '/language/' . $short_element);
		$this->parent->setPath('extension_root', $this->parent->getPath('source'));
		$manifest_details = JInstaller::parseXMLInstallFile($this->parent->getPath('manifest'));
		$this->parent->extension->manifest_cache = json_encode($manifest_details);
		$this->parent->extension->state = 0;
		$this->parent->extension->name = $manifest_details['name'];
		$this->parent->extension->enabled = 1;

		try
		{
			$this->parent->extension->store();
		}
		catch (RuntimeException $e)
		{
			JLog::add(JText::_('JLIB_INSTALLER_ERROR_LANG_DISCOVER_STORE_DETAILS'), JLog::WARNING, 'jerror');

			return false;
		}
		return $this->parent->extension->extension_id;
	}

	/**
	 * Refreshes the extension table cache
	 *
	 * @return  boolean result of operation, true if updated, false on failure
	 *
	 * @since   3.1
	 */
	public function refreshManifestCache()
	{
		$client = JApplicationHelper::getClientInfo($this->parent->extension->client_id);
		$manifestPath = $client->path . '/language/' . $this->parent->extension->element . '/' . $this->parent->extension->element . '.xml';

		return $this->doRefreshManifestCache($manifestPath);
	}
}

/**
 * Deprecated class placeholder. You should use JInstallerAdapterLanguage instead.
 *
 * @package     Joomla.Libraries
 * @subpackage  Installer
 * @since       3.1
 * @deprecated  4.0
 * @codeCoverageIgnore
 */
class JInstallerLanguage extends JInstallerAdapterLanguage
{
}
