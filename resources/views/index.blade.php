@extends('layout')
@section('content')
<style>
  .push-top {
    margin-top: 50px;
  }
</style>
<div class="push-top">
  @if(session()->get('success'))
    <div class="alert alert-success">
      {{ session()->get('success') }}  
    </div><br />
  @endif
  @if(session()->get('error'))
  <div class="alert alert-danger">
    {{ session()->get('error') }}  
  </div><br />
@endif
  <span><a href="{{ route('task.create') }}" class="btn btn-success mb-3 float-right">Add Task</a></span>
  <table class="table">
    <thead>
        <tr class="table-dark">
          <td>ID</td>
          <td>Name</td>
          <td>Email</td>
          <td>Phone</td>
          <td>Task</td>
          <td class="text-center">Action</td>
        </tr>
    </thead>
    <tbody>
        @foreach($task as $info)
        <tr>
            <td>{{$info->id}}</td>
            <td>{{$info->name}}</td>
            <td>{{$info->email}}</td>
            <td>{{$info->phone}}</td>
            <td>{{$info->task}}</td>
            <td class="text-center">
                <a href="{{ route('task.edit', $info->id)}}" class="btn btn-primary btn-sm"> Edit</a>
                <form action="{{ route('task.destroy', $info->id)}}" method="post" style="display: inline-block">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger btn-sm" type="submit"> Delete</button>
                  </form>
            </td>
        </tr>
        @endforeach
    </tbody>
    {{ $task->links(('pagination::bootstrap-4')) }}

  </table>
<div>
@endsection