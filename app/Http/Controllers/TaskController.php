<?php

namespace App\Http\Controllers;

use App\Mail\EmailDemo;
use Illuminate\Http\Request;
use App\Models\Task;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // $task = Task::all();
        $task = Task::latest()->paginate(5);

        return view('index', compact('task'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $storeData = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|max:255',
            'phone' => 'required|numeric',
            'task' => 'required|max:255',
        ]);
        $task = Task::create($storeData);
        if($task){
            $email = $task->email;
   
            $mailData = [
                'name' => $task->name,
                'email' => $task->email,
                'phone' => $task->phone,
                'task' => $task->task,
            ];
      
            Mail::to($email)->send(new EmailDemo($mailData));
            return redirect('/task')->with('success', 'Task has been created!');

        }else{
        return redirect('/task')->with('success', 'Task has been created!');

        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $task = Task::findOrFail($id);
        return view('edit', compact('task'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $updateData = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|max:255',
            'phone' => 'required|numeric',
            'task' => 'required|max:255',
        ]);
        Task::whereId($id)->update($updateData);
        return redirect('/task')->with('success', 'Task has been updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $task = Task::findOrFail($id);
        $task->delete();
        return redirect('/task')->with('error', 'Task has been deleted');
    }
}
