<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChannelCategory;
use App\Models\LiveTelevision;
use App\Rules\FileTypeValidate;
use Illuminate\Http\Request;

class LiveTelevisionController extends Controller {

    public function category() {
        $pageTitle         = 'Television Categories';
        $channelCategories = ChannelCategory::searchable(['name'])->latest()->paginate(getPaginate());
        return view('admin.television.category', compact('channelCategories', 'pageTitle'));
    }

    public function categoryStore(Request $request, $id = 0) {
        $request->validate([
            'name'  => 'required|string|unique:channel_categories,name,' . $id,
            'price' => 'required|numeric|gte:0',
        ]);

        if (!$id) {
            $category     = new ChannelCategory();
            $notification = 'Category added successfully.';
        } else {
            $category     = ChannelCategory::findOrFail($id);
            $notification = 'Category updated successfully';
        }

        $category->name  = $request->name;
        $category->price = $request->price;
        $category->save();

        $notify[] = ['success', $notification];
        return back()->withNotify($notify);
    }

    public function categoryStatus($id) {
        return ChannelCategory::changeStatus($id);
    }

    public function channel() {
        $pageTitle   = "All Channels";
        $televisions = LiveTelevision::searchable(['title', 'category:name'])->latest()->with('category')->paginate(getPaginate());
        $categories  = ChannelCategory::active()->get(['id', 'name']);
        return view('admin.television.index', compact('pageTitle', 'televisions', 'categories'));
    }

    public function channelStore(Request $request, $id = 0) {
        $imageValidate = $id == 0 ? 'required' : 'nullable';
        $request->validate([
            'title'               => 'required|max: 40|unique:live_televisions,title,' . $id,
            'url'                 => 'required|url',
            'description'         => 'required|string',
            'channel_category_id' => 'required|integer|exists:channel_categories,id',
            'image'               => [$imageValidate, 'image', new FileTypeValidate(['jpeg', 'jpg', 'png'])],
        ]);

        if ($id == 0) {
            $television   = new LiveTelevision();
            $notification = 'Television added successfully.';
        } else {
            $television   = LiveTelevision::findOrFail($id);
            $notification = 'Television updated successfully';
        }

        if ($request->hasFile('image')) {
            try {
                $television->image = fileUploader($request->image, getFilePath('television'), getFileSize('television'), @$television->image);
            } catch (\Exception $e) {
                $notify[] = ['error', 'Image could not be uploaded'];
                return back()->withNotify($notify);
            }
        }

        $television->title               = $request->title;
        $television->url                 = $request->url;
        $television->description         = $request->description;
        $television->channel_category_id = $request->channel_category_id;
        $television->save();

        $notify[] = ['success', $notification];
        return back()->withNotify($notify);
    }

    public function channelDelete(Request $request) {
        $television = LiveTelevision::findOrFail($request->id);
        fileManager()->removeFile(getFilePath('television') . '/' . $television->image);
        $television->delete();

        $notify[] = ['success', 'Television deleted successfully'];
        return back()->withNotify($notify);
    }

    public function channelStatus($id) {
        return LiveTelevision::changeStatus($id);
    }
}
