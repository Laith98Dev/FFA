<?php

namespace Laith98Dev\FFA\providers;

/*  
 *  A plugin for PocketMine-MP.
 *  
 *	 _           _ _   _    ___   ___  _____             
 *	| |         (_) | | |  / _ \ / _ \|  __ \            
 *	| |     __ _ _| |_| |_| (_) | (_) | |  | | _____   __
 *	| |    / _` | | __| '_ \__, |> _ <| |  | |/ _ \ \ / /
 *	| |___| (_| | | |_| | | |/ /| (_) | |__| |  __/\ V / 
 *	|______\__,_|_|\__|_| |_/_/  \___/|_____/ \___| \_/  
 *	
 *	Copyright (C) 2022 Laith98Dev
 *  
 *	Youtube: Laith Youtuber
 *	Discord: Laith98Dev#0695
 *	Github: Laith98Dev
 *	Email: help@laithdev.tk
 *	Donate: https://paypal.me/Laith113
 *
 *	This program is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 	
 */

use Laith98Dev\FFA\Main;
use Laith98Dev\FFA\utils\SQLKeyStorer;

use poggit\libasynql\libasynql;
use poggit\libasynql\SqlError;
use poggit\libasynql\DataConnector;

class DefaultProvider {

    private DataConnector $db;

    public function __construct(
        private Main $plugin
        ){
        $this->init();
    }

    public function init(){
        if ($this->plugin->isDisabled()) return;

        try {
            $this->db = $db = libasynql::create(
                $this->plugin,
                $this->plugin->getConfig()->get("database"),
                ["sqlite" => "sqlite.sql"]
            );
        } catch (\Error $e) {
            $this->plugin->getLogger()->error($e->getMessage());
            return;
        }

        $error = null;
        $db->executeGeneric(
            SQLKeyStorer::INIT,
            [],
            null,
            function (SqlError $error_) use (&$error) {
                $error = $error_;
            }
        );
		
        $db->waitAll();

        if ($error !== null) {
            $this->plugin->getLogger()->error($error->getMessage());
        }
    }

    public function db(){
        return $this->db;
    }

}