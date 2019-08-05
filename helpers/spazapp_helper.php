<?php
    function get_defult_page($user){
            
            switch ($user->default_usergroup) {
            #Admin
            case 1:
                $page = 'admin';
                break;
            #Public
            case 2:
                $page = 'store_owner';
                break;
            #Default
            case 3:
                $page = 'store_owner';
                break;
            #Rep
            case 4:
                $page = 'rep';
                break;
            #National Sales Manager
            case 5:
                $page = 'store_owner';
                break;
            #Area Sales Manager
            case 6:
                $page = 'store_owner';
                break;
            #Logistics
            case 7:
                $page = 'store_owner';
                break;
            #Store Owner
            case 8:
                $page = 'store_owner';
                break;
            #Wholesaler
            case 10:
                $page = 'store_owner';
                break;
            // #Distributor
            // case 11:
            //     $page = 'distributor';
            //     break;

            #ums_store
            case 11:
                $page = 'ums_store';
                break;

            #Parent
            case 14:
                $page = 'parent';
                break;
            #Marchent
            case 15:
                $page = 'merchant';
                break;
            #supplier           
            case 17:
                $page = 'supplier';
                break;
                #traders           
            case 19:
                $page = 'trader';
                break;
            
            #libertylife
            case 20:
                $page = 'libertylife';
                break;

            #cocacola
            case 21:
                $page = 'cocacola';
                break;

            #Admin
            case 16:
                $page = 'taptuckadmin';
                break;
            #taptuckRep
            case 22:
                $page = 'taptuckrep';
                break;

            #InsMasterCompany
            case 23:
                $page = 'Ins_master_company';
                break;

            #InsAgency
            case 24:
                $page = 'ins_agency';
                break;

            #InsBranch
            case 25:
                $page = 'ins_branch';
                break;

           
            #InsBranch
            case 26:
            case 27:
            case 28:
            case 29:
            case 30:
                $page = 'sales_agent';
                break;

            #data_capturer
            case 31:
                $page = 'data_capturer';
                break;

            #traders           
            case 34:
                $page = 'trader';
                break;

            #public
            default:
                $page = 'store_owner';
                break;
        }

        return $page;
    }