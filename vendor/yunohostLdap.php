<?php 

 /**
  *  YunoHost - Self-hosting for all
  *  Copyright (C) 2012  Kload <kload@kload.fr>
  *
  *  This program is free software: you can redistribute it and/or modify
  *  it under the terms of the GNU Affero General Public License as
  *  published by the Free Software Foundation, either version 3 of the
  *  License, or (at your option) any later version.
  *
  *  This program is distributed in the hope that it will be useful,
  *  but WITHOUT ANY WARRANTY; without even the implied warranty of
  *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  *  GNU Affero General Public License for more details.
  *
  *  You should have received a copy of the GNU Affero General Public License
  *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
  */

class YunoHostLdap extends LdapEntry
{
	public function __construct($server, $domain, $modelPath) 
	{
		parent::__construct($server, $domain, $modelPath);
	}

	public function installBackground() 
	{

		//$_SESSION['first-install'] = true;

		flash('error', T_('Please execute post-install manually (as root): yunohost-cli tools postinstall'));

		redirect_to('/');
	}

	public function backgroundInstalled() 
	{
		if ($this->connection) return $this->findOneBy(array('ou' => 'users')); //TODO: check others
	}

	public function connectAs($uid, $password, $connectAsAdmin = false)
	{
		$this->searchPath = array('ou' => 'users');
		$this->attributesToFetch = array('cn');
		$result = $this->findOneBy(array('uid' => $uid));
		$userDnArray = array('cn' => $result['cn'], 'ou' => 'users');
		if ($connectAsAdmin) {	
			if ($this->connect($userDnArray, $password)) {
				$this->populateAdmin(array('cn' => 'admin'));
		        $members = $this->getAdminMember();
		        
		        foreach ($members as $member)
		        {
		        	if ($member == $this->arrayToDn($userDnArray).$this->baseDn)
		        		return true;
		       	}
		       	return false;
		    } else return false;
	    } else return $this->connect($userDnArray, $password);
	}

	public function grantAdmin($uid) {
		if ($this->user->description === 'admin')
        {
            $this->populateAdmin(array('cn' => 'admin'));
            $members = $this->getAdminMember();
            if (!is_array($members))
                $members = array($members);

            $newMember = 'cn='.$this->user->cn.',ou=users,'.$this->baseDn;

            foreach ($members as $member)
            	if ($member === $newMember) return false;

            $members[] = $newMember;

            $this->setAdminMember($members);
            $this->saveAdmin();
        }
	}

	public function revokeAdmin($uid) {
            $this->populateAdmin(array('cn' => 'admin'));
            $members = $this->getAdminMember();

            if (!is_array($members))
                return false;

            $revokeMember = 'cn='.$this->user->cn.',ou=users,'.$this->baseDn;

            foreach ($members as $key => $member)
            	if ($member === $revokeMember) unset($members[$key]);

            $this->setAdminMember($members);
            $this->saveAdmin();
	}

	protected function ldapError() {
		$error = 'Error: ('. ldap_errno($this->connection) .') '. ldap_error($this->connection);
		flash('error', $error);
	}
}
