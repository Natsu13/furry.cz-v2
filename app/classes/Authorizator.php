<?php

use Nette\Diagnostics\Debugger;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\ApplicationException;
use Nette\Application\BadRequestException;

/**
* Authorizes users to perform tasks.
*
* Per-user permissions are represented by following array of booleans:
	'CanListContent'              => Specified in Database/Permissions
	'CanViewContent'              => Specified in Database/Permissions
	'CanDeleteContent'            => Only owner (given in Database/Ownership) and admins can delete content.
	'CanEditContentAndAttributes' => Specified in Database/Permissions
	'CanEditHeader'               => Specified in Database/Permissions
	'CanEditOwnPosts'             => Specified in Database/Permissions
	'CanDeleteOwnPosts'           => Specified in Database/Permissions
	'CanReadPosts'                => Specified in Database/Permissions
	'CanDeletePosts'              => Specified in Database/Permissions
	'CanWritePosts'               => Specified in Database/Permissions
	'CanEditPermissions'          => Specified in Database/Permissions
	'CanEditPolls'                => Specified in Database/Permissions
	'IsOwner'                     => Is user owner? (given in Database/Ownership)

* Default permissions are represented by the same array as above.
* The values apply only to approved users, except:
	'CanListContent' ~ Applies to any user
	'CanViewContent' ~ Applies to any user
*/
class Authorizator extends \Nette\Object
{
	private $database;



	public function __construct($database)
	{
		$this->database = $database;
	}



	private function cascadeDeny(&$access, $field)
	{
		if ($field == 'CanViewContent')
		{
			$access['CanViewContent'] = false;
			$access['CanEditContentAndAttributes'] = false;
			$access['CanEditHeader'] = false;
			$access['CanDeletePosts'] = false;
			$access['CanEditPermissions'] = false;
			$access['CanEditPolls'] = false;
		}
		if ($field == 'CanReadPosts' || $field == 'CanViewContent')
		{
			$access['CanReadPosts'] = false;
			$access['CanWritePosts'] = false;
			$access['CanEditOwnPosts'] = false;
			$access['CanDeleteOwnPosts'] = false;
		}
	}



	/** Checks all permission of given user to given content.
	* @return array A table with permissions (matching SQL table Permissions)
	*/
	public function authorize($content, $user)
	{
		// CHECK FULL ACCESS

		if (! $user->isLoggedIn())
		{
			$perms = array( // Nope! :)
				"CanListContent" => false,
				"CanViewContent" => false,
				"CanDeleteContent" => false,
				"CanEditContentAndAttributes" => false,
				"CanEditHeader" => false,
				"CanEditOwnPosts" => false,
				"CanDeleteOwnPosts" => false,
				"CanReadPosts" => false,
				"CanDeletePosts" => false,
				"CanWritePosts" => false,
				"CanEditPermissions" => false,
				"CanEditPolls" => false,
				"IsOwner" => false
			);

			if ($content["IsForRegisteredOnly"] || $content["IsForAdultsOnly"])
			{
				return $perms;
			}
			else
			{
				$permsDb = $content->ref('DefaultPermissions');

				$perms["IsOwner"] = false;
				$perms["CanListContent"] = $permsDb["CanListContent"];
				$perms["CanViewContent"] = $permsDb["CanViewContent"];

				return $perms;
			}
		}

		$overlord = $user->isInRole('admin');
		$isOwner = false;
		if (!$overlord)
		{
			// Check isOwner (owner is an overlord)
			$isOwner = $this->database->table('Ownership')->where(array(
				'ContentId' => $content['Id'],
				'UserId' => $user->id
			))->count() > 0;
			$overlord = $isOwner;
		}

		if ($overlord)
		{
			return array(
				'CanListContent' => true,
				'CanViewContent' => true,
				'CanDeleteContent' => true,
				'CanEditContentAndAttributes' => true,
				'CanEditHeader' => true,
				'CanEditOwnPosts' => true,
				'CanDeleteOwnPosts' => true,
				'CanReadPosts' => true,
				'CanDeletePosts' => true,
				'CanWritePosts' => true,
				'CanEditPermissions' => true,
				'CanEditPolls' => true,
				'IsOwner' => true
			);
		}

		// CHECK USER-SPECIFIC RIGHTS

		$access = $this->database->table('Access')->where(array(
			'ContentId' => $content['Id'],
			'UserId' => $user->id
		))->fetch();

		if ($access !== false)
		{
			$perms = $access->ref('PermissionId')->toArray();

		}
		else // No specific permissions => defaults are in effect
		{
			$perms = $content->ref('DefaultPermissions')->toArray();
		}
		unset($perms['Id']);

		// CHECK CONTENT FLAGS
	
		if (!$content['IsDiscussionAllowed'])
		{
			$this->cascadeDeny($perms, 'CanReadPosts');
		}

		if ($content['IsForRegisteredOnly'] && !$user->isInRole('approved'))
		{
			$this->cascadeDeny($perms, 'CanViewContent');
		}

		if ($content['IsForAdultsOnly'] && !$user->isInRole('adult'))
		{
			$this->cascadeDeny($perms, 'CanViewContent');
		}	
		
		$perms["IsOwner"] = false;

		return $perms;
	}



	/**
	* @throw ForbiddenRequestException if user is'n logged or his/her account isn't approved
	*/
	public function verifyAccountApproved(\Nette\Security\User $user)
	{
		list($result, $message) = $this->isAccountApproved($user);
		if (! $result)
		{
			throw new ForbiddenRequestException($message);
		}
	}



	/**
	* @return array($status:bool, $message:string)
	*/
	public function isAccountApproved(\Nette\Security\User $user)
	{
		if (! $user->isLoggedIn())
		{
			return array(false, "Nejste přihlášen(a)");
		}
		if (! $user->isInRole('approved'))
		{
			return array(false, "Váš uživatelský účet není schválen");
		}
		return array(true, "Všechno v pořádku");
	}
}
