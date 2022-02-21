<?php

namespace App\Http\Controllers;

use App\Model\User;
use App\Model\Intake;
use App\Model\Branch;
use App\Model\Courses;
use App\Helper\Common;
use App\Model\Criteria;
use App\Model\Institution;
use App\Model\Scholarship;
use App\Model\FieldOfStudy;
use App\Model\LevelOfStudy;
use App\Model\Subdiscipline;
use App\Model\ContactPerson;
use App\Model\InstitutionUser;
use App\Model\ScholarshipType;
use App\Model\ScholarshipTypes;
use App\Model\ScholarshipIntake;
use App\Http\Requests\ImportCsv;
use App\Model\ScholarshipCriteria;
use App\Model\ScholarshipProvider;
use App\Model\CourseSubdiscipline;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use App\Model\IntakeScholarshipIntake;
use App\Model\ScholarshipProviderUser;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class ImportController extends Controller {

    public $institutionTable = 'institution';
    public $scholarshipproviderTable = 'scholarshipprovider';
    public $scholarshipTable = 'scholarship';
    public $branchTable = 'branch';
    public $courseTable = 'course';

    public function exportLevelOfStudy() {
        $los = LevelOfStudy::get()->toArray();
        $createExportData = [];
        $createExportData[] = ['Level Of Study Id', 'Level Of Study Name'];
        foreach ($los as $key => $value) {
            unset($value['created_at']);
            unset($value['updated_at']);
            $createExportData[] = $value;
        }
        $exportLevelOfStudyUrl = Common::writeExcelfile($createExportData);
        return $exportLevelOfStudyUrl;
    }

    public function exportFieldOfStudy() {
        $los = FieldOfStudy::get()->toArray();
        $createExportData = [];
        $createExportData[] = ['Field Of Study Id', 'Field Of Study Name'];
        foreach ($los as $key => $value) {
            unset($value['created_at']);
            unset($value['updated_at']);
            $createExportData[] = $value;
        }
        $exportLevelOfStudyUrl = Common::writeExcelfile($createExportData);
        return $exportLevelOfStudyUrl;
    }

    public function exportSubdiscipline() {
        $los = Subdiscipline::get()->toArray();
        $createExportData = [];
        $createExportData[] = ['Subdiscipline Id', 'Related FieldofStudy Id', 'Subdiscipline Name'];
        foreach ($los as $key => $value) {
            unset($value['created_at']);
            unset($value['updated_at']);
            $createExportData[] = $value;
        }
        $exportLevelOfStudyUrl = Common::writeExcelfile($createExportData);
        return $exportLevelOfStudyUrl;
    }

    public function typeOfScholarship() {
        $los = ScholarshipType::select('id', 'type_name')->get()->toArray();
        $createExportData = [];
        $createExportData[] = ['Scholarship Type Id', 'Scholarship Type Name'];
        foreach ($los as $key => $value) {
            $createExportData[] = $value;
        }
        $exportLevelOfStudyUrl = Common::writeExcelfile($createExportData);
        return $exportLevelOfStudyUrl;
    }

    public function criteriaSpecificScholarship() {
        $los = Criteria::select('id', 'criteria_name')->get()->toArray();
        $createExportData = [];
        $createExportData[] = ['Criteria Id', 'Criteria  Name'];
        foreach ($los as $key => $value) {
            $createExportData[] = $value;
        }
        $exportLevelOfStudyUrl = Common::writeExcelfile($createExportData);
        return $exportLevelOfStudyUrl;
    }

    public function importDatabaseView() {
        if (!in_array(Auth::user()->Role_id, [User::ADMIN, User::INTERNAL_REVIEWER])) {
            abort(401);
        }
        return view('import.importDatabaseView');
    }

    public function importDatabaseSelect(ImportCsv $request) {
        if (!in_array(Auth::user()->Role_id, [User::ADMIN, User::INTERNAL_REVIEWER])) {
            abort(401);
        }
        $input = Input::all();

        //$filePath = public_path().'/test/ImportTempl  ates.csv';
        $file = $input['csvFile'];
        $file_ex = $file->getClientOriginalExtension();
        $file_name = uniqid() . '.' . $file_ex;
        $file->move(base_path() . '/public/temp/', $file_name);


        $filePath = public_path() . '/temp/' . $file_name;
        $tableName = $input['tableName'];

        $structure = [];
        //print_r($structure);die();
        if ($tableName == User::TABLE_INSTITUTION) {
            $model = new Institution();
            $tableColumns = $model->getTableColumns();
            $structure = [
                'tableNumber' => User::TABLE_INSTITUTION,
                'tableName' => $this->institutionTable,
                'columns' => $tableColumns
            ];
        }
        if ($tableName == User::TABLE_SCHOLARSHIP_PROVIDER) {
            $model = new ScholarshipProvider();
            $tableColumns = $model->getTableColumns();
            $structure = [
                'tableNumber' => User::TABLE_SCHOLARSHIP_PROVIDER,
                'tableName' => $this->scholarshipproviderTable,
                'columns' => $tableColumns
            ];
        }
        if ($tableName == User::TABLE_SCHOLARSHIP) {
            $model = new Scholarship();
            $tableColumns = $model->getTableColumns();
            $structure = [
                'tableNumber' => User::TABLE_SCHOLARSHIP,
                'tableName' => $this->scholarshipTable,
                'columns' => $tableColumns
            ];
        }
        if ($tableName == User::TABLE_BRANCHES) {
            $model = new Branch();
            $tableColumns = $model->getTableColumns();
            $structure = [
                'tableNumber' => User::TABLE_BRANCHES,
                'tableName' => $this->branchTable,
                'columns' => $tableColumns
            ];
        }
        if ($tableName == User::TABLE_COURSES) {
            $model = new Courses();
            $tableColumns = $model->getTableColumns();
            $structure = [
                'tableNumber' => User::TABLE_COURSES,
                'tableName' => $this->courseTable,
                'columns' => $tableColumns
            ];
        }

        $excelcontent = Common::readExcelFile($filePath);
        foreach ($excelcontent as $key => $value) {
            $structure['excelColumns'] = $value['columns'];
            break;
        }

        $selecttable = $structure['tableName'];
        $structure['path'] = $file_name;

        return view('import.importDatabaseView', compact('structure'));
    }

    public function importMapColumns() {
        if (!in_array(Auth::user()->Role_id, [User::ADMIN, User::INTERNAL_REVIEWER])) {
            abort(401);
        }
        $input = Input::all();
//        print_r($input);die;

        $filename = $input['path'];

        $filePath = public_path() . '/temp/' . $input['path'];
        $newstructure = Common::readExcelFile($filePath);

        foreach ($newstructure as $key => $value) {
            $structure['excelColumns'] = $value['columns'];
            $structure['excelvalues'] = $value['values'];
            break;
        }

        $db_data = [];
//        print_r($structure);die;

        foreach ($structure['excelvalues'] as $key => $value) {
            $flipdata = array_combine($structure['excelColumns'], $value);
            $db_data[] = $flipdata;
        }
        //print_r($db_data);die();
        //$input = array_flip($input);
        $tableName = strtolower($input['tableName']);
        unset($input['_token']);
        unset($input['path']);
        unset($input['tableName']);
        //print_r(array_filter($input));die;
        //$filterInput = array_flip(array_filter($input));
        $filterInput = array_filter($input);
        //print_r($filterInput);die;

        $databaseInsertArray = [];
        $tableColumns = array_keys($input);
        /* print_r($db_data);
          print_r($filterInput);die(); */
        $counterkey = 0;
        foreach ($db_data as $key => $value) {
            foreach ($value as $key1 => $value1) {
                foreach ($filterInput as $key2 => $value2) {
                    if ($key1 == $value2) {
                        $databaseInsertArray[$counterkey][$key2] = $value1;
                    }
                }
            }
            $counterkey++;
        }
//        print_r($databaseInsertArray);die;

        $databaseInsertArray['path'] = $filename;
        $databaseInsertArray['tableName'] = $tableName;
        $choosenfields = $input;
        return view('import.importDatabaseView', compact('databaseInsertArray', 'choosenfields'));
    }

    public function importValueSelect() {

        if (!in_array(Auth::user()->Role_id, [User::ADMIN, User::INTERNAL_REVIEWER])) {
            abort(401);
        }
        $input = Input::all();
        $checklist = $input['checklist'];

        $fields = $input['fields'];
        $fields = json_decode($fields, true);
        //print_r($fields);die();

        $filename = $input['path'];

        $filePath = public_path() . '/temp/' . $input['path'];
        $newstructure = Common::readExcelFile($filePath);
        foreach ($newstructure as $key => $value) {
            $structure['excelColumns'] = $value['columns'];
            $structure['excelvalues'] = $value['values'];
            break;
        }
        $db_data = [];
        foreach ($structure['excelvalues'] as $key => $value) {
            $flipdata = array_combine($structure['excelColumns'], $value);
            $db_data[] = $flipdata;
        }
        //$input = array_flip($input);
        $tableName = strtolower($input['tableName']);
        unset($input['_token']);
        unset($input['path']);
        unset($input['tableName']);
        //print_r(array_filter($input));die;
        //$filterInput = array_flip(array_filter($input));
        $filterInput = array_filter($fields);
        //print_r($filterInput);die;

        $databaseInsertArray = [];
        $tableColumns = array_keys($fields);
        /* print_r($db_data);
          print_r($filterInput);die(); */
        $counterkey = 0;
        foreach ($db_data as $key => $value) {
            foreach ($value as $key1 => $value1) {
                foreach ($filterInput as $key2 => $value2) {
                    if ($key1 == $value2) {
                        $databaseInsertArray[$counterkey][$key2] = $value1;
                    }
                }
            }
            $counterkey++;
        }
        foreach ($checklist as $key => $value) {
            if (array_key_exists($value, $databaseInsertArray)) {
                $finalInsertArray[] = $databaseInsertArray[$value];
            }
        }
        $incorrectData = [];
        $correctData = [];
        foreach ($finalInsertArray as $k => $v) {
            if ($tableName == $this->institutionTable) {
                $returnContents = $this->addInstitutions($v);
                if ($returnContents['status'] == false) {
                    $returnContents['data']['content']['errors'] = json_encode($returnContents['data']['errors']);
                    $incorrectData[] = $returnContents['data']['content'];
                }
                if ($returnContents['status']) {
                    $filterInput['Institution_id'] = 'Institution Id';
                    $correctData[] = $returnContents['data'];
                }
            }
            if ($tableName == $this->scholarshipproviderTable) {
                $returnContents = $this->addScholarshipProvider($v);
                if ($returnContents['status'] == false) {
                    $returnContents['data']['content']['errors'] = json_encode($returnContents['data']['errors']);
                    $incorrectData[] = $returnContents['data']['content'];
                }
                if ($returnContents['status']) {
                    $filterInput['ScholarshipProvider_id'] = 'Scholarship Provider Id';
                    $correctData[] = $returnContents['data'];
                }
            }
            if ($tableName == $this->branchTable) {
                $returnContents = $this->branchSubmit($v);
                if ($returnContents['status'] == false) {
                    $returnContents['data']['content']['errors'] = json_encode($returnContents['data']['errors']);
                    $incorrectData[] = $returnContents['data']['content'];
                }
                if ($returnContents['status']) {
                    $filterInput['Branch_id'] = 'Branch Id';
                    $correctData[] = $returnContents['data'];
                }
            }
            if ($tableName == $this->courseTable) {
                $returnContents = $this->courseSubmit($v);
                if ($returnContents['status'] == false) {
                    $returnContents['data']['content']['errors'] = json_encode($returnContents['data']['errors']);
                    $incorrectData[] = $returnContents['data']['content'];
                }
                if ($returnContents['status']) {
                    $filterInput['Course_id'] = 'Course Id';
                    $filterInput['Intake_id'] = 'Intake Id';
                    $filterInput['Intake_id2'] = 'Intake Id2';
                    $filterInput['Intake_id3'] = 'Intake Id3';
                    $correctData[] = $returnContents['data'];
                }
            }
            if ($tableName == $this->scholarshipTable) {
                $returnContents = $this->scholarshipSubmit($v);
                if ($returnContents['status'] == false) {
                    $returnContents['data']['content']['errors'] = json_encode($returnContents['data']['errors']);
                    $incorrectData[] = $returnContents['data']['content'];
                }
                if ($returnContents['status']) {
                    $filterInput['Scholarship_id'] = 'Scholarship Id';
                    $correctData[] = $returnContents['data'];
                }
            }
        }
        //export error data
        $fileName = '';
        $correctFile = '';
        if (count($incorrectData) > 0) {
            $excelField = array_keys($filterInput);
            $excelField[] = 'errors';
            $incorrectDataNew = [];
            foreach ($incorrectData as $k => $v) {
                foreach ($v as $kk => $vv) {
                    if (in_array($kk, $excelField)) {
                        if ($kk == 'errors') {
                            $incorrectDataNew[$k]['errors'] = $vv;
                        } else {
                            $incorrectDataNew[$k][$filterInput[$kk]] = $vv;
                        }
                    }
                }
            }
            $fileName = Common::writeExcelfile($incorrectDataNew);
            Session::put('error', 'Some data does not imported, Please check exported excel file');
        }
        if (count($correctData) > 0) {
            $excelField = array_keys($filterInput);
            $excelField[] = 'id';
            $correctDataNew = [];
            foreach ($correctData as $k => $v) {
                foreach ($v as $kk => $vv) {
                    if (in_array($kk, $excelField)) {
                        if ($kk == 'id') {
                            $correctDataNew[$k]['id'] = $vv;
                        } else {
                            $correctDataNew[$k][$filterInput[$kk]] = $vv;
                        }
                    }
                }
            }
            $correctFile = Common::writeExcelfile($correctDataNew);
            Session::put('info', 'Imported successfully');
        }
        return redirect()->route('importDatabaseView')->with(['errorFile' => $fileName, 'correctFile' => $correctFile]);
    }

    public function addInstitutions($data) {

        Validator::extend('phone', function($attribute, $value, $parameters, $validator) {
            $response = (preg_match('%^(?:(?:\(?(?:00|\+)([1-4]\d\d|[1-9]\d?)\)?)?[\-\.\ \\\/]?)?((?:\(?\d{1,}\)?[\-\.\ \\\/]?){0,})(?:[\-\.\ \\\/]?(?:#|ext\.?|extension|x)[\-\.\ \\\/]?(\d+))?$%i', $value) && strlen($value) >= 10 && strlen($value) <= 12) ? true : false;
            return $response;
        });
        Validator::replacer('phone', function($message, $attribute, $rule, $parameters) {
            return str_replace(':attribute', $attribute, ':attribute is invalid phone number');
        });
        Validator::extend('domain', function($attribute, $value, $parameters, $validator) {
            return preg_match('/^(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/m', $value);
        });
        Validator::replacer('domain', function($message, $attribute, $rule, $parameters) {
            return str_replace(':attribute', $attribute, ':attribute is invalid domain');
        });

        $validator = Validator::make($data, [
                    'firstName' => 'required|regex:/^[a-zA-Z][a-zA-Z\\s]+$/',
                    'lastName' => 'required|regex:/^[a-zA-Z][a-zA-Z\\s]+$/',
                    'email' => 'required|email|unique:user,email,' . User::DELETED . ',is_deleted',
                    'phone' => 'required|numeric|phone',
                    'name' => 'required|regex:/^[a-zA-Z][a-zA-Z.\\s]+$/|unique:institution,name',
                    'domain' => 'required|domain',
                    'cricosCode' => 'required',
                    'type' => 'in:' . Institution::TYPE_UNIVERSITY . ',' . Institution::TYPE_TRAINING,
                        ], [
                    'firstName.required' => 'Please enter First Name.',
                    'lastName.required' => 'Please enter Last Name.',
                    'email.required' => 'Please enter Email.',
                    'email.unique' => 'Email specified already exists on the system. Please use another one.',
                    'cricosCode.required' => 'Please enter CricosCode.',
                    'name.required' => 'Please enter Institution Name.',
                    'name.unique' => 'Name specified already exists on the system. Please use another one.',
                    'Role_id.in' => 'Selected User Type is invalid.',
                    'type.in' => 'Selected Type is invalid.',
                    'domain.required' => 'Please enter Institution Domain.'
        ]);
        if ($validator->fails()) {
            $returnData = [
                'content' => $data,
                'errors' => $validator->messages()
            ];
            return $this->sendResponse(false, '', 'Data incorrect.', $returnData);
        }

//        to approve institution start
        $data['approval'] = Institution::APPROVAL;
        $data['User_approvedby'] = Auth::id();
//        to approve institution end
        $data['Role_id'] = User::INSTITUTION_ADMIN_USER;
        $data['verification'] = Institution::VERIFICATION_PASS;
        $data['User_verifiedby'] = Auth::id();
        $uniquePassword = uniqid();
        $data['password'] = bcrypt($uniquePassword);
        $create_user = User::create($data);
        $data['slug'] = Institution::slug($data['name']);
        $crete_institute = Institution::create($data);
        $data['User_id'] = $create_user->id;
        $data['Institution_id'] = $crete_institute->id;
        InstitutionUser::create($data);
        Institution::createInstitutionPage($crete_institute->id);
        //create notification for created institute
        $create_user['password'] = $uniquePassword;
        $data['id'] = $crete_institute->id;
        Common::sendMail($create_user->email, 'Uniexplorers', $create_user, 'mail.adminInstituteAdd');
//        unset approve institution start
        unset($data['approval']);
        unset($data['User_approvedby']);
//        unset approve institution end
        return $this->sendResponse(true, '', 'Institution has been added successfully.', $data);
    }

    public function addScholarshipProvider($data) {

        Validator::extend('phone', function($attribute, $value, $parameters, $validator) {
            $response = (preg_match('%^(?:(?:\(?(?:00|\+)([1-4]\d\d|[1-9]\d?)\)?)?[\-\.\ \\\/]?)?((?:\(?\d{1,}\)?[\-\.\ \\\/]?){0,})(?:[\-\.\ \\\/]?(?:#|ext\.?|extension|x)[\-\.\ \\\/]?(\d+))?$%i', $value) && strlen($value) >= 10 && strlen($value) <= 12) ? true : false;
            return $response;
        });
        Validator::replacer('phone', function($message, $attribute, $rule, $parameters) {
            return str_replace(':attribute', $attribute, ':attribute is invalid phone number');
        });

        Validator::extend('domain', function($attribute, $value, $parameters, $validator) {
            return preg_match('/^(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/m', $value);
        });
        Validator::replacer('domain', function($message, $attribute, $rule, $parameters) {
            return str_replace(':attribute', $attribute, ':attribute is invalid Website/Domain');
        });

        $validator = Validator::make($data, [
                    'firstName' => 'required|regex:/^[a-zA-Z][a-zA-Z\\s]+$/',
                    'lastName' => 'required|regex:/^[a-zA-Z][a-zA-Z\\s]+$/',
                    'phone' => 'required|numeric|phone',
                    'name' => 'required|regex:/^[a-zA-Z][a-zA-Z.\\s]+$/',
                    'URL' => 'required|domain',
                    'email' => 'required|email|unique:user,email,' . User::DELETED . ',is_deleted'
                        ], [
                    'name.required' => 'Please enter Scholarship Provider Name.',
                    'name.string' => 'Please enter valid Scholarship Provider Name.',
                    'firstName.required' => 'Please enter First Name.',
                    'lastName.required' => 'Please enter Last Name.',
                    'email.required' => 'Please enter Email.',
                    'email.unique' => 'Email specified already exists on the system. Please use another one.',
                    'URL.required' => 'Please enter URL.'
        ]);
        if ($validator->fails()) {
            $returnData = [
                'content' => $data,
                'errors' => $validator->messages()
            ];
            return $this->sendResponse(false, '', 'Data incorrect.', $returnData);
        }

        $uniquePassword = uniqid();
        $data['password'] = bcrypt($uniquePassword);
        $data['Role_id'] = User::SCHOLARSHIP_PROVIDER_ADMIN_USER;
        $data['visibility'] = ScholarshipProvider::PUBLISHED_VISIBILITY;
        $data['verified'] = ScholarshipProvider::VERIFIED;
        $data['approval'] = ScholarshipProvider::APPROVAL;
        $data['User_approvedby'] = Auth::id();
        $crete_provider = ScholarshipProvider::create($data);
        $create_user = User::create($data);
        $data['User_id'] = $create_user['id'];
        $data['ScholarshipProvider_id'] = $crete_provider->id;
        ScholarshipProviderUser::create($data);
        $create_user['password'] = $uniquePassword;
        Common::sendMail($create_user->email, 'Uniexplorers', $create_user, 'mail.addscholarship_provider');
        $data['ScholarshipProvider_id'] = $crete_provider->id;
        return $this->sendResponse(true, '', 'Scholarship Provider added successfully', $data);
    }

    public function branchSubmit($data) {

        Validator::extend('phone', function($attribute, $value, $parameters, $validator) {
            $response = (preg_match('%^(?:(?:\(?(?:00|\+)([1-4]\d\d|[1-9]\d?)\)?)?[\-\.\ \\\/]?)?((?:\(?\d{1,}\)?[\-\.\ \\\/]?){0,})(?:[\-\.\ \\\/]?(?:#|ext\.?|extension|x)[\-\.\ \\\/]?(\d+))?$%i', $value) && strlen($value) >= 10 && strlen($value) <= 12) ? true : false;
            return $response;
        });
        Validator::replacer('phone', function($message, $attribute, $rule, $parameters) {
            return str_replace(':attribute', $attribute, 'Phone is invalid phone number');
        });

        Validator::extend('domain', function($attribute, $value, $parameters, $validator) {
            return preg_match('/^(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/m', $value);
        });
        Validator::replacer('domain', function($message, $attribute, $rule, $parameters) {
            return str_replace(':attribute', $attribute, 'Url is invalid.');
        });

        $validator = Validator::make($data, [
                    'name' => 'required|max:50|regex:/^[a-zA-Z][a-zA-Z\\s]+$/',
                    'type' => 'required|in:' . Branch::TYPE_OFFLINE . ',' . Branch::TYPE_ONLINE,
                    'Institution_id' => 'required|numeric',
                    'streetAddress' => 'required|max:255',
                    'streetAddress2' => 'required|max:255',
                    'zipcode' => 'required|not_in:0',
                    'URLApplicationFee' => 'required|domain',
                    'URLTuitionFee' => 'required|domain',
                    'ContactPerson_name_payment' => 'required|max:50|regex:/^[a-zA-Z][a-zA-Z\\s]+$/',
                    'ContactPerson_email_payment' => 'required|email',
                    'ContactPerson_phone_payment' => 'required|numeric|phone',
                    //
                    'ContactPerson_name' => 'required|max:50|regex:/^[a-zA-Z][a-zA-Z\\s]+$/',
                    'ContactPerson_email' => 'required|email',
                    'ContactPerson_phone' => 'required|numeric|phone',
                    //
                    'ContactPerson_name_tution' => 'required|max:50|regex:/^[a-zA-Z][a-zA-Z\\s]+$/',
                    'ContactPerson_email_tution' => 'required|email',
                    'ContactPerson_phone_tution' => 'required|numeric|phone',
                        ], [
                    'name.required' => 'Branch name is blank.',
                    'name.regex' => 'Branch Name is not valid.',
                    'Institution_id.required' => 'Please enter Institution Id',
                    'Institution_id.numeric' => 'Please enter numeric value for Institution Id',
                    'name.max' => 'Name may not be grater then 50 characters.',
                    'streetAddress.required' => 'Branch Street Address is blank.',
                    'streetAddress.max' => 'Branch Street Address may not be grater then 255 characters.',
                    'streetAddress2.max' => 'Branch Street Address may not be grater then 255 characters.',
                    'zipcode.required' => 'Please enter Postal / Zip code.',
                    'URLApplicationFee.required' => 'Please Enter Application Free Url',
                    'URLTuitionFee.required' => 'Please Enter Tution Free Url',
                    //
                    'ContactPerson_name_payment.required' => 'Please Enter Name of Office responsible of Application Fee Payment.',
                    'ContactPerson_name_payment.regex' => 'Name format not valid.',
                    'ContactPerson_name_payment.max' => 'Name may not be grater then 50 characters.',
                    'ContactPerson_email_payment.required' => 'Please enter Email for Office responsible of Application Fee Payment.',
                    'ContactPerson_email_payment.email' => 'Email is not valid',
                    'ContactPerson_phone_payment.required' => 'Please Enter Phone of Office responsible of Application Fee Payment.',
                    'ContactPerson_phone_payment.numeric' => 'Phone of Office responsible of Application Fee Payment must be a number.',
                    //
                    'ContactPerson_name.required' => 'Please Enter Name of Staff for Commission.',
                    'ContactPerson_name.regex' => 'Name format not valid.',
                    'ContactPerson_name.max' => 'Name may not be grater then 50 characters.',
                    'ContactPerson_email.required' => 'Please enter Email of Staff for Commission.',
                    'ContactPerson_email.email' => 'Email is not valid',
                    'ContactPerson_phone.required' => 'Please enter Phone of Staff for Commission.',
                    'ContactPerson_phone.numeric' => 'Phone of Staff for Commission must be a number.',
                    //tution
                    'ContactPerson_name_tution.required' => 'Please enter Name for Office responsible of Tuition Fee Payment.',
                    'ContactPerson_name_tution.regex' => 'Name format not valid.',
                    'ContactPerson_name_tution.max' => 'Name may not be grater then 50 characters.',
                    'ContactPerson_email_tution.required' => 'Please enter Email for Office responsible of Tuition Fee Payment.',
                    'ContactPerson_email_tution.email' => 'Email is not valid.',
                    'ContactPerson_phone_tution.required' => 'Please enter Phone for Office responsible of Tuition Fee Payment.',
                    'ContactPerson_phone_tution.numeric' => 'Phone for Office responsible of Tuition Fee Payment must be a number.',
        ]);
        if ($validator->fails()) {
            $returnData = [
                'content' => $data,
                'errors' => $validator->messages()
            ];
            return $this->sendResponse(false, '', 'Data incorrect.', $returnData);
        }


        $getInstitutoin = Institution::find($data['Institution_id']);
        if (!$getInstitutoin) {
            $errors = [
                    ['Institution_id' => 'Institution does not exists.']
            ];
            $errors = json_encode($errors);
            $returnData = [
                'content' => $data,
                'errors' => $errors
            ];

            return $this->sendResponse(false, '', 'Data incorrect.', $returnData);
        }



        $branchData = [
            'Institution_id' => $data['Institution_id'],
            'name' => $data['name'],
            'streetAddress' => $data['streetAddress'],
            'streetAddress2' => $data['streetAddress2'],
            'zipcode' => $data['zipcode'],
            'type' => $data['type'],
            'URLApplicationFee' => $data['URLApplicationFee'],
            'URLTuitionFee' => $data['URLTuitionFee']
        ];
        $createdBranch = Branch::create($branchData);
        $contactPersonDetail[] = [
            'Branch_id' => $createdBranch->id,
            'name' => $data['ContactPerson_name_payment'],
            'email' => $data['ContactPerson_email_payment'],
            'phone' => $data['ContactPerson_phone_payment'],
            'type' => ContactPerson::APPLICATION_FEE
        ];
        $contactPersonDetail[] = [
            'Branch_id' => $createdBranch->id,
            'name' => $data['ContactPerson_name'],
            'email' => $data['ContactPerson_email'],
            'phone' => $data['ContactPerson_phone'],
            'type' => ContactPerson::COMMISSION
        ];
        $contactPersonDetail[] = [
            'Branch_id' => $createdBranch->id,
            'name' => $data['ContactPerson_name_tution'],
            'email' => $data['ContactPerson_email_tution'],
            'phone' => $data['ContactPerson_phone_tution'],
            'type' => ContactPerson::TUITION_FEE
        ];
        if ($createdBranch) {
            foreach ($contactPersonDetail as $contactPersonData) {
                ContactPerson::create($contactPersonData);
            }
        }
        $data['Branch_id'] = $createdBranch->id;
        return $this->sendResponse(true, route('contactInformation'), 'Institute Update Successfully', $data);
    }

    public function courseSubmit($data) {

        Validator::extend('domain', function($attribute, $value, $parameters, $validator) {
            return preg_match('/^(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/m', $value);
        });
        Validator::replacer('domain', function($message, $attribute, $rule, $parameters) {
            return str_replace(':attribute', $attribute, ':attribute is invalid Website/Domain');
        });

        Validator::extend('domain', function($attribute, $value, $parameters, $validator) {
            return preg_match('/^(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/m', $value);
        });
        Validator::replacer('domain', function($message, $attribute, $rule, $parameters) {
            return str_replace(':attribute', $attribute, 'Url is invalid.');
        });

        $rule = [
            'name' => 'required|regex:/^[a-zA-Z][a-zA-Z.\\s]+$/',
            'URL' => 'required|domain',
            'cricosCode' => 'required',
            'overview' => 'required',
            'requirements' => 'required',
            'LevelofStudy_id' => 'required|numeric',
            'Subdiscipline_id' => 'required',
            'IELTS' => 'numeric',
            'TOEFL' => 'numeric',
            'PTE' => 'numeric',
            'CAE' => 'numeric',
            'videoURL' => 'domain',
            //intake data1
            'Branch_id' => 'required',
            'mode' => 'required',
            'tuitionFeesEP' => 'numeric',
            'tuitionFeesPA' => 'numeric',
            'nonTuitionFee' => 'numeric',
            'tuitionFeeURL' => 'domain',
            'applicationFeeURL' => 'domain',
            //intake data2
            'Branch_id2' => 'required',
            'mode2' => 'required',
            'tuitionFeesEP2' => 'numeric',
            'tuitionFeesPA2' => 'numeric',
            'nonTuitionFee2' => 'numeric',
            'tuitionFeeURL2' => 'domain',
            'applicationFeeURL2' => 'domain',
            //intake data3
            'Branch_id3' => 'required',
            'mode3' => 'required',
            'tuitionFeesEP3' => 'numeric',
            'tuitionFeesPA3' => 'numeric',
            'nonTuitionFee3' => 'numeric',
            'tuitionFeeURL3' => 'domain',
            'applicationFeeURL3' => 'domain',
        ];

        $messages = [
            'name.required' => 'Please enter Course Name.',
            'name.string' => 'Please enter valid Course Name.',
            'name.unique' => 'Course name already exist. Please select another name.',
            'URL.required' => 'Please enter Course URL.',
            'cricosCode.required' => 'Please enter course cricosCode.',
            'overview.required' => 'Please enter course overview.',
            'requirements.required' => 'Please enter entry requirements.',
            'LevelofStudy_id.required' => 'Please enter Level of Study Id.',
            'LevelofStudy_id.numeric' => 'Please enter Level of Study Id.',
            'Subdiscipline_id.required' => 'Please enter Subdiscipline Id.',
            //intake validatin message
            'Branch_id.required' => 'Please Select Branch.',
            'mode.required' => 'Please select mode.',
            'tuitionFeesEP.numeric' => 'Tuition Fees(Entire Programme) must be a number.',
            'tuitionFeesPA.numeric' => 'Tuition Fees(Per Annum) must be a number.',
            'nonTuitionFee.numeric' => 'Nontuition Fees(Entire Programme) must be a number.',
            //intake2 validatin message
            'Branch_id2.required' => 'Please Select Branch.',
            'mode2.required' => 'Please select mode.',
            'tuitionFeesEP2.numeric' => 'Tuition Fees(Entire Programme) must be a number.',
            'tuitionFeesPA2.numeric' => 'Tuition Fees(Per Annum) must be a number.',
            'nonTuitionFee2.numeric' => 'Nontuition Fees(Entire Programme) must be a number.',
            //intake3 validatin message
            'Branch_id3.required' => 'Please Select Branch.',
            'mode3.required' => 'Please select mode.',
            'tuitionFeesEP3.numeric' => 'Tuition Fees(Entire Programme) must be a number.',
            'tuitionFeesPA3.numeric' => 'Tuition Fees(Per Annum) must be a number.',
            'nonTuitionFee3.numeric' => 'Nontuition Fees(Entire Programme) must be a number.'
        ];

        $validator = Validator::make($data, $rule, $messages);

        if ($validator->fails()) {
            $returnData = [
                'content' => $data,
                'errors' => $validator->messages()
            ];
            return $this->sendResponse(false, '', 'Data incorrect.', $returnData);
        }


        $name = $data['name'];
        $getcheckBranch = Branch::find($data['Branch_id']);
        $InstitutionUser = isset($getcheckBranch->getInstitution->id) ? $getcheckBranch->getInstitution->id : '';

        if (!$InstitutionUser) {
            $errors = [
                    ['Branch_id' => 'Branch id (Intake 1) related Institution does not exists.']
            ];
            $errors = json_encode($errors);
            $returnData = [
                'content' => $data,
                'errors' => $errors
            ];
            return $this->sendResponse(false, '', 'Data incorrect.', $returnData);
        }


        $course = Courses::whereHas('getIntakes', function($q) use ($InstitutionUser) {
                    return $q->whereHas('getIntakeBranch', function($ib) use ($InstitutionUser) {
                                return $ib->whereHas('getInstitution', function($i) use ($InstitutionUser) {
                                            return $i->where(['id' => $InstitutionUser]);
                                        });
                            });
                })
                ->where(['name' => $name]);
        $course = $course->first();
//        for intake 2
        $getcheckBranch = Branch::find($data['Branch_id2']);
        $InstitutionUser = isset($getcheckBranch->getInstitution->id) ? $getcheckBranch->getInstitution->id : '';
        if (!$InstitutionUser) {
            $errors = [
                    ['Branch_id2' => 'Branch id (Intake 2) related Institution does not exists.']
            ];
            $errors = json_encode($errors);
            $returnData = [
                'content' => $data,
                'errors' => $errors
            ];
            return $this->sendResponse(false, '', 'Data incorrect.', $returnData);
        }
        $course2 = Courses::whereHas('getIntakes', function($q) use ($InstitutionUser) {
                    return $q->whereHas('getIntakeBranch', function($ib) use ($InstitutionUser) {
                                return $ib->whereHas('getInstitution', function($i) use ($InstitutionUser) {
                                            return $i->where(['id' => $InstitutionUser]);
                                        });
                            });
                })
                ->where(['name' => $name]);
        $course2 = $course2->first();
//        for intake 2
        $getcheckBranch = Branch::find($data['Branch_id3']);
        $InstitutionUser = isset($getcheckBranch->getInstitution->id) ? $getcheckBranch->getInstitution->id : '';
        if (!$InstitutionUser) {
            $errors = [
                    ['Branch_id3' => 'Branch id (Intake 3) related Institution does not exists.']
            ];
            $errors = json_encode($errors);
            $returnData = [
                'content' => $data,
                'errors' => $errors
            ];
            return $this->sendResponse(false, '', 'Data incorrect.', $returnData);
        }
        $course3 = Courses::whereHas('getIntakes', function($q) use ($InstitutionUser) {
                    return $q->whereHas('getIntakeBranch', function($ib) use ($InstitutionUser) {
                                return $ib->whereHas('getInstitution', function($i) use ($InstitutionUser) {
                                            return $i->where(['id' => $InstitutionUser]);
                                        });
                            });
                })
                ->where(['name' => $name]);
        $course3 = $course3->first();
        if ($course || $course2 || $course3) {
            $rule['name'] = 'required|unique:course,name|regex:/^[a-zA-Z][a-zA-Z.\\s]+$/';
        } else {
            $rule['name'] = 'required|regex:/^[a-zA-Z][a-zA-Z.\\s]+$/';
        }

        $validator = Validator::make($data, $rule, $messages);

        if ($validator->fails()) {
            $returnData = [
                'content' => $data,
                'errors' => $validator->messages()
            ];
            return $this->sendResponse(false, '', 'Data incorrect.', $returnData);
        }

        $subdisciplineId = explode(',', $data['Subdiscipline_id']);
        foreach ($subdisciplineId as $k => $v) {
            if (!is_numeric($v)) {
                $errors = [
                        ['Subdiscipline_id' => 'Scholarship Id can be only numbers']
                ];
                $errors = json_encode($errors);
                $returnData = [
                    'content' => $data,
                    'errors' => $errors
                ];
                return $this->sendResponse(false, '', 'Data incorrect.', $returnData);
            }
        }
//        print_r($data);die('sdf');

        $data["step_count"] = 1;
        $subdisciplineId = explode(',', $data['Subdiscipline_id']);
        unset($data['Subdiscipline_id']);
        $getBranch = Branch::find($data['Branch_id']);
        $data["User_editedby"] = $getBranch->getInstitution->getInstitutionAdmin->getUser->id;
        $data['slug'] = Courses::slug($data['name']);
        $course_data = Courses::create($data);
        foreach ($subdisciplineId as $subValue) {
            $subData = ['subdiscipline_id' => $subValue, 'course_id' => $course_data->id];
            CourseSubdiscipline::create($subData);
        }
        $institutionDetail_slug = $getBranch->getInstitution->slug;
        Courses::createCoursePage($course_data->id, $institutionDetail_slug);

        $data['Course_id'] = $course_data->id;
        $intake = Intake::create($data);
//        $dataIntake2 = $data;
        $dataIntake2 = [
            'Course_id' => $course_data->id,
            'Branch_id' => $data['Branch_id2'],
            'mode' => $data['mode2'],
            'commencementDate' => isset($data['commencementDate2']) ? $data['commencementDate2'] : NULL,
            'applicationStartDate' => isset($data['applicationStartDate2']) ? $data['applicationStartDate2'] : NULL,
            'applicationDeadlineDate' => isset($data['applicationDeadlineDate2']) ? $data['applicationDeadlineDate2'] : NULL,
            'TuitionfeeDate' => isset($data['TuitionfeeDate2']) ? $data['TuitionfeeDate2'] : NULL,
            'tuitionDeadlineDate' => isset($data['tuitionDeadlineDate2']) ? $data['tuitionDeadlineDate2'] : NULL,
            'duration' => isset($data['duration2']) ? $data['duration2'] : NULL,
            'commissionStartDate' => isset($data['commissionStartDate2']) ? $data['commissionStartDate2'] : NULL,
            'commissionDeadlineDate' => isset($data['commissionDeadlineDate2']) ? $data['commissionDeadlineDate2'] : NULL,
            'tuitionFeesEP' => isset($data['tuitionFeesEP2']) ? $data['tuitionFeesEP2'] : NULL,
            'tuitionFeesPA' => isset($data['tuitionFeesPA2']) ? $data['tuitionFeesPA2'] : NULL,
            'nonTuitionFee' => isset($data['nonTuitionFee2']) ? $data['nonTuitionFee2'] : NULL,
            'tuitionFeeURL' => isset($data['tuitionFeeURL2']) ? $data['tuitionFeeURL2'] : NULL,
            'applicationFeeURL' => isset($data['applicationFeeURL2']) ? $data['applicationFeeURL2'] : NULL,
        ];
        $intake2 = Intake::create($dataIntake2);
        $dataIntake3 = $data;
        $dataIntake3 = [
            'Course_id' => $course_data->id,
            'Branch_id' => $data['Branch_id3'],
            'mode' => $data['mode3'],
            'commencementDate' => isset($data['commencementDate3']) ? $data['commencementDate3'] : NULL,
            'applicationStartDate' => isset($data['applicationStartDate3']) ? $data['applicationStartDate3'] : NULL,
            'applicationDeadlineDate' => isset($data['applicationDeadlineDate3']) ? $data['applicationDeadlineDate3'] : NULL,
            'TuitionfeeDate' => isset($data['TuitionfeeDate3']) ? $data['TuitionfeeDate3'] : NULL,
            'tuitionDeadlineDate' => isset($data['tuitionDeadlineDate3']) ? $data['tuitionDeadlineDate3'] : NULL,
            'duration' => isset($data['duration3']) ? $data['duration3'] : NULL,
            'commissionStartDate' => isset($data['commissionStartDate3']) ? $data['commissionStartDate3'] : NULL,
            'commissionDeadlineDate' => isset($data['commissionDeadlineDate3']) ? $data['commissionDeadlineDate3'] : NULL,
            'tuitionFeesEP' => isset($data['tuitionFeesEP3']) ? $data['tuitionFeesEP3'] : NULL,
            'tuitionFeesPA' => isset($data['tuitionFeesPA3']) ? $data['tuitionFeesPA3'] : NULL,
            'nonTuitionFee' => isset($data['nonTuitionFee3']) ? $data['nonTuitionFee3'] : NULL,
            'tuitionFeeURL' => isset($data['tuitionFeeURL3']) ? $data['tuitionFeeURL3'] : NULL,
            'applicationFeeURL' => isset($data['applicationFeeURL3']) ? $data['applicationFeeURL3'] : NULL,
        ];

        $intake3 = Intake::create($dataIntake3);
        $data['Intake_id'] = $intake->id;
        $data['Intake_id2'] = $intake2->id;
        $data['Intake_id3'] = $intake3->id;

        return $this->sendResponse(true, '', 'Course Import Successfully', $data);
    }

    public function scholarshipSubmit($data) {

        Validator::extend('domain', function($attribute, $value, $parameters, $validator) {
            return preg_match('/^(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/m', $value);
        });
        Validator::replacer('domain', function($message, $attribute, $rule, $parameters) {
            return str_replace(':attribute', $attribute, ':attribute is invalid Website/Domain');
        });


        $rule = [
            'ScholarshipProvider_id' => 'required',
            'name' => 'required|unique:scholarship,name,' . Scholarship::DELETE . ',is_deleted|regex:/^[a-zA-Z][a-zA-Z\\s]+$/',
            'URL' => 'required|domain',
            'Type_id' => 'numeric',
            'Criteria_id' => 'numeric',
            'type_name' => 'string',
            'criteria_name' => 'string',
            'Intake_id' => 'numeric',
            'details' => 'required',
            'entryRequirements' => 'required',
            'videoURL' => 'domain',
            'CGPA' => 'numeric',
            'appFormURL' => 'domain',
            'brochureURL' => 'domain',
        ];

        $message = [
            'name.required' => 'Please enter Scholarship Name.',
            'name.unique' => 'The Scholarship name already exists. Please enter a different one.',
            'URL.required' => 'Please enter URL.',
            'Type_id.numeric' => 'Scholarship Type id can be only number.',
            'Criteria_id.numeric' => 'Criteria Id Type id can be only number.',
            'ScholarshipProvider_id.required' => 'Please select Scholarship Provider Id',
            'Intake_id.required' => 'Please select Course for add Intake',
            'entryRequirements.required' => 'Please Provide Entry Requirements',
            'details.required' => 'Please Provide Details Overview',
//            create IntakeScholarshipIntake
        ];

        $validator = Validator::make($data, $rule, $message);
        if ($validator->fails()) {
            $returnData = [
                'content' => $data,
                'errors' => $validator->messages()
            ];
            return $this->sendResponse(false, '', 'Data incorrect.', $returnData);
        }


        if (isset($data['Type_id']) && !empty($data['Type_id']) && ($data['Type_id'] != '')) {
            $checkScholarshipType = ScholarshipType::find($data['Type_id']);
            if (!$checkScholarshipType) {
                $errors = [
                        ['Type_id' => 'No type found for given type Id.']
                ];
                $errors = json_encode($errors);
                $returnData = [
                    'content' => $data,
                    'errors' => $errors
                ];
                return $this->sendResponse(false, '', 'Data incorrect.', $returnData);
            }
        } elseif (isset($data['type_name']) && empty($data['type_name'])) {
            $errors = [
                    ['type_name' => 'Please Enter Type Name.']
            ];
            $errors = json_encode($errors);
            $returnData = [
                'content' => $data,
                'errors' => $errors
            ];
            return $this->sendResponse(false, '', 'Data incorrect.', $returnData);
        }

        if (isset($data['Criteria_id']) && !empty($data['Criteria_id']) && ($data['Criteria_id'] != '')) {
            $checkScholarshipCriteria = Criteria::find($data['Criteria_id']);
            if (!$checkScholarshipCriteria) {
                $errors = [
                        ['Criteria_id' => 'No criteria found for given type Id.']
                ];
                $errors = json_encode($errors);
                $returnData = [
                    'content' => $data,
                    'errors' => $errors
                ];
                return $this->sendResponse(false, '', 'Data incorrect.', $returnData);
            }
        } elseif (isset($data['criteria_name']) && empty($data['criteria_name'])) {
            $errors = [
                    ['criteria_name' => 'Please Enter Criteria Name.']
            ];
            $errors = json_encode($errors);
            $returnData = [
                'content' => $data,
                'errors' => $errors
            ];
            return $this->sendResponse(false, '', 'Data incorrect.', $returnData);
        }


        $getcheckIntake = Intake::find($data['Intake_id']);
        if (!$getcheckIntake) {
            $errors = [
                    ['Intake_id' => 'Intake id based Intake does not exists.']
            ];
            $errors = json_encode($errors);
            $returnData = [
                'content' => $data,
                'errors' => $errors
            ];
            return $this->sendResponse(false, '', 'Data incorrect.', $returnData);
        }

        $scholarshpProviderChekck = ScholarshipProviderUser::where(['ScholarshipProvider_id' => $data['ScholarshipProvider_id']])->first();
        if (!$scholarshpProviderChekck) {
            $errors = [
                    ['ScholarshipProvider_id' => 'Scholarship Provider does not exist.']
            ];
            $errors = json_encode($errors);
            $returnData = [
                'content' => $data,
                'errors' => $errors
            ];
            return $this->sendResponse(false, '', 'Data incorrect.', $returnData);
        }



        if (isset($data['Type_id']) && !empty($data['Type_id']) && ($data['Type_id'] != '')) {
            $checkScholarshipType = ScholarshipType::find($data['Type_id']);
            $data['type_name'] = $checkScholarshipType->type_name;
        } elseif (isset($data['type_name']) && !empty($data['type_name'])) {
            $checkScholarshipType = ScholarshipType::where(['type_name' => $data['type_name']])->first();
            if (!$checkScholarshipType) {
                $data['unique'] = 1;
                $addtype = ScholarshipType::create($data);
                $data['Type_id'] = $addtype->id;
            } else {
                $data['Type_id'] = $checkScholarshipType->id;
            }
        }

        if (isset($data['Criteria_id']) && !empty($data['Criteria_id']) && ($data['Criteria_id'] != '')) {
            $checkScholarshipCriteria = Criteria::find($data['Criteria_id']);
            $data['criteria_name'] = $checkScholarshipCriteria->criteria_name;
        } elseif (isset($data['criteria_name']) && !empty($data['criteria_name'])) {
            $checkScholarshipCriteria = Criteria::where(['criteria_name' => $data['criteria_name']])->first();
            if (!$checkScholarshipCriteria) {
                $data['unique'] = 1;
                $addCriteria = Criteria::create($data);
                $data['Criteria_id'] = $addCriteria->id;
            } else {
                $data['Criteria_id'] = $checkScholarshipCriteria->id;
            }
        }


        $data['slug'] = Scholarship::slug($data['name']);
        $added_Scholarship = Scholarship::create($data);
        $data['Scholarship_id'] = $added_Scholarship->id;
        $added_scholarshipintake = ScholarshipIntake::create($data);
        //create scholarship type
        ScholarshipTypes::create($data);
        ScholarshipCriteria::create($data);
        //create scholarship criteria
        $data['ScholarshipIntake_id'] = $added_scholarshipintake->id;
        IntakeScholarshipIntake::create($data);

        return $this->sendResponse(true, '', 'Scholarship added successfully.', $data);
    }

//    public function callAction($method, $parameters) {
//        // code that runs before any action
//        $userRoleAction = [
//            User::ADMIN => [
//                'exportLevelOfStudy', 'exportFieldOfStudy', 'exportSubdiscipline', 'typeOfScholarship',
//                'criteriaSpecificScholarship', 'importDatabaseView', 'importDatabaseSelect', 'importMapColumns',
//                'importValueSelect', 'addInstitutions', 'addScholarshipProvider', 'branchSubmit', 'courseSubmit',
//                'scholarshipSubmit'
//            ],
//            User::INTERNAL_REVIEWER => [
//                'exportLevelOfStudy', 'exportFieldOfStudy', 'exportSubdiscipline', 'typeOfScholarship',
//                'criteriaSpecificScholarship', 'importDatabaseView', 'importDatabaseSelect', 'importMapColumns',
//                'importValueSelect', 'addInstitutions', 'addScholarshipProvider', 'branchSubmit', 'courseSubmit',
//                'scholarshipSubmit'
//            ],
//            User::INTERNAL_REVIEWER => [
//                'exportLevelOfStudy', 'exportFieldOfStudy', 'exportSubdiscipline', 'typeOfScholarship',
//                'criteriaSpecificScholarship', 'importDatabaseView', 'importDatabaseSelect', 'importMapColumns',
//                'importValueSelect', 'addInstitutions', 'addScholarshipProvider', 'branchSubmit', 'courseSubmit',
//                'scholarshipSubmit'
//            ],
//            User::INSTITUTION_ADMIN_USER => [
//                'exportLevelOfStudy', 'exportFieldOfStudy', 'exportSubdiscipline', 'typeOfScholarship',
//                'criteriaSpecificScholarship', 'importDatabaseView', 'importDatabaseSelect', 'importMapColumns',
//                'importValueSelect', 'addInstitutions', 'addScholarshipProvider', 'branchSubmit', 'courseSubmit',
//                'scholarshipSubmit'
//            ],
//            User::INSTITUTION_USER => [
//                'exportLevelOfStudy', 'exportFieldOfStudy', 'exportSubdiscipline', 'typeOfScholarship',
//                'criteriaSpecificScholarship', 'importDatabaseView', 'importDatabaseSelect', 'importMapColumns',
//                'importValueSelect', 'addInstitutions', 'addScholarshipProvider', 'branchSubmit', 'courseSubmit',
//                'scholarshipSubmit'
//            ],
//            User::SCHOLARSHIP_PROVIDER_ADMIN_USER => [
//                'exportLevelOfStudy', 'exportFieldOfStudy', 'exportSubdiscipline', 'typeOfScholarship',
//                'criteriaSpecificScholarship', 'importDatabaseView', 'importDatabaseSelect', 'importMapColumns',
//                'importValueSelect', 'addInstitutions', 'addScholarshipProvider', 'branchSubmit', 'courseSubmit',
//                'scholarshipSubmit'
//            ],
//            User::SCHOLARSHIP_PROVIDER_USER => [
//                'exportLevelOfStudy', 'exportFieldOfStudy', 'exportSubdiscipline', 'typeOfScholarship',
//                'criteriaSpecificScholarship', 'importDatabaseView', 'importDatabaseSelect', 'importMapColumns',
//                'importValueSelect', 'addInstitutions', 'addScholarshipProvider', 'branchSubmit', 'courseSubmit',
//                'scholarshipSubmit'
//            ]
//        ];
//
//        if (!isset($userRoleAction[Auth::user()->Role_id]) || !in_array($method, $userRoleAction[Auth::user()->Role_id])) {
//            return abort(404);
//        }
//        if (!$this->isAccess($method)) {
//            return abort(401, Common::UNAUTHORIZED_401_MESSAGE);
//        }
//        return parent::callAction($method, $parameters);
//    }
//
//    public function isAccess($method) {
//        //There must be only one function name exist in all of array.
//        $permiddionAction = [
//            Permission::IMPORT_EXCEL_FILE_INTERNAL_REVIEWER => [
//                'exportLevelOfStudy', 'exportFieldOfStudy', 'exportSubdiscipline', 'typeOfScholarship',
//                'criteriaSpecificScholarship', 'importDatabaseView', 'importDatabaseSelect', 'importMapColumns',
//                'importValueSelect', 'addInstitutions', 'addScholarshipProvider', 'branchSubmit', 'courseSubmit',
//                'scholarshipSubmit'
//            ],
//            Permission::IMPORT_EXCEL_FILE_INSTITUTION => [
//                'exportLevelOfStudy', 'exportFieldOfStudy', 'exportSubdiscipline', 'typeOfScholarship',
//                'criteriaSpecificScholarship', 'importDatabaseView', 'importDatabaseSelect', 'importMapColumns',
//                'importValueSelect', 'addInstitutions', 'addScholarshipProvider', 'branchSubmit', 'courseSubmit',
//                'scholarshipSubmit'
//            ],
//            Permission::IMPORT_EXCEL_FILE_SCHOLARSHIP_PROVIDER => [
//                'exportLevelOfStudy', 'exportFieldOfStudy', 'exportSubdiscipline', 'typeOfScholarship',
//                'criteriaSpecificScholarship', 'importDatabaseView', 'importDatabaseSelect', 'importMapColumns',
//                'importValueSelect', 'addInstitutions', 'addScholarshipProvider', 'branchSubmit', 'courseSubmit',
//                'scholarshipSubmit'
//            ]
//        ];
//
////        'addInstitutions','manageUser','deleteInstitutionUser','editInstitutionUserView','editInstitutionUser'
//        $permission = Auth::user()->Role_id;
//        $user = User::find(Auth::id());
//        $accessableUser = [User::ADMIN, User::INSTITUTION_ADMIN_USER, User::SCHOLARSHIP_PROVIDER_ADMIN_USER];
//        if (in_array($permission, $accessableUser)) {
//            return true;
//        }
//        $return = false;
//        foreach ($user->getUserRolePermission as $key => $val) {
//            $permissionId = $val->getRolePermission->getPermission->id;
//            if (isset($permiddionAction[$permissionId]) && in_array($method, $permiddionAction[$permissionId])) {
//                $return = true;
//            }
//        }
//        return $return;
//    }
}
