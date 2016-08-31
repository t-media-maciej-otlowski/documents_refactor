<?php

namespace Documents;

use Documents\Document;
use Import\DocumentImport;
//use Maatwebsite\Excel\Facades\Excel;
//use Documents\DocumentGroup;
use Maatwebsite\Excel\Excel;
class DocumentsController extends \ServerController {

    public function listDocuments() {
        try {
            $params = \Input::all();
            $validator = \Validator::make($params, [
                        'with_group' => 'boolean',
                        'with_attributes' => 'boolean'
            ]);
            if ($validator->fails()) {
                return self::resposeJson($validator->errors(), 'error', null);
            }

            $query = Document::whereNull('deleted_at');

            if (isset($params['with_attributes']) && $params['with_attributes']) {
                $query->with('attributes');
            }
            if (isset($params['with_group']) && $params['with_group']) {
                $query->with('group');
            }
            if (isset($params['documents_groups__id']) && $params['documents_groups__id']) {
                $query->where('documents_groups__id', $params['documents_groups__id']);
            }
            \DB::beginTransaction();
            $documents = $query->get();
            \DB::commit();
            return self::responseJson($documents);
        } catch (Exception $ex) {
            \DB::rollback();
            return self::resposeJson($ex->getMessage(), 'error', null);
        }
    }

    public function addDocuments() {
        try {
            $param = \Input::all();
            $validator = \Validator::make($param, [
                        'documents_groups__id' => 'required|numeric|exists:documents_groups,id',
                        'name' => 'string',
                        'description' => 'string',
                        'type' => 'string',
                        'order_number' => 'integer',
                        'user__id' => 'integer',
            ]);
            if ($validator->fails()) {
                return self::responseJson($validator->errors(), 'error', null);
            }
            $document_group_id = DocumentGroup::where('id', '=', $param['documents_groups__id'])->first();
            // $document = Document::where('documents_groups_id','=',$param['id'])
            if (!$document_group_id) {
                return self::responseJson('DocumentGroup does not exist', 'error', null);
            }
            $document = Document::create($param);

            return self::responseJson($document);
        } catch (Exception $ex) {
            return self::responseJson($ex->getMessage(), 'error', null);
        }
    }

    public function updateDocuments() {
        try {
            $param = \Input::all();
            $validator = \Validator::make($param, [
                        'id' => 'required|numeric|exists:documents',
                        'name' => 'string',
                        'description' => 'string',
                        'type' => 'string',
                        'order_number' => 'integer',
                        'user__id' => 'integer'
            ]);
            if ($validator->fails()) {
                return self::responseJson($validator->errors(), 'error', null);
            }
            $document = Document::where('id', '=', $param['id'])->first();
            if (!$document) {
                return self::responseJson('Document does not exist', 'error', null);
            }
            $document->update($param);
            return self::responseJson($document);
        } catch (Exception $ex) {

            return self::responseJson($ex->getMessage(), 'error', null);
        }
    }

    public function deleteDocuments() {
        try {
            $param = \Input::all();
            $validator = \Validator::make($param, array(
                        'id' => 'numeric|exists:documents',
                        'documents_groups__id' => 'numeric|exists:documents',
            ));
            if ($validator->fails()) {
                return self::responseJson($validator->errors(), 'error', null);
            }
            //list selected document by id(array:1 document)
            if (isset($param['id']) && ($param['id'])) {
                $documents = Document::where('id', '=', $param['id'])
                        ->get();
            }
            //list all documents of selected group(array:N documents)
            if (isset($param['documents_groups__id']) && ($param['documents_groups__id'])) {
                $documents = Document::where('documents_groups__id', '=', $param['documents_groups__id'])
                        ->get();
            }
            if (empty($documents)) {
                return self::responseJson('Documents not found', 'error', null);
            }
            \DB::beginTransaction();
            foreach ($documents as $index => $document) {
                $document->delete();
            }
            \DB::commit();
            return self::responseJson($documents);
        } catch (Exception $ex) {
            DB::rollback();
            return self::responseJson($ex->getMessage(), 'error', null);
        }
    }
      
    
}
