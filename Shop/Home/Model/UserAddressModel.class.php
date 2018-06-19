<?php
/**
 * 用户收货地址模型
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Home\Model;
use Think\Model;
class UserAddressModel extends Model{
    protected $_validate = array(
        array('first_name','require','The value "First Name" is required.',0),
        array('last_name','require','The value "Last Name" is required.',0),
        array('country','require','The value "Country" is required.',0),
        array('state','require','The value "State" is required.',0),
        array('city','require','The value "City" is required.',0),
        array('address','require','The value "Address" is required.',0),
        array('zipcode','require','The value "Zip" is required.',0),
        array('telephone','require','The value "Phone" is required.',0),
        array('email','require','The value "Email" is required.',0),
        array('email','email','The email address should be valid.',0),
        //array('company_name','require','The value "Company" is required.',0),
        array('type','require','The value "Preferred" is required.',0),
        array('password','require','The value "Password" is required.',0),
        array('repassword','password','Two passwords do not match.',0,'confirm'),
    );
}


?>