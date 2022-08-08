<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;

class PruebasController extends Controller{
    public function index(){
        $titulo = "animales";
        $animales = ['perro', 'gato', 'tigre'];
        return view('pruebas.index',[
            'titulo' => $titulo,
            'animales' => $animales
        ]);
    }
    public function testOrm(){
        $posts = Post::all();
       foreach ($posts as $post) {
            echo '<pre>';
            echo '<h1>'.  $post->title . '</h1>';
            echo '<span>'.$post->user->name. '-'.$post->category->name.'</span>';
            echo '<p>'.  $post->content . '</p>';
            echo '<pre>';
        }
        $categories = Category::all();
        foreach ($categories as $category) {
            echo '<pre>';
            echo '<h1>'.  $category->name . '</h1>';
            echo '<p>'.  $category->crate_at . '</p>';
            echo '<pre>';
        }
        $users = User::all();
        foreach ($users as $user) {
            echo '<pre>';
            echo '<h1>'.  $user->name .' '.$user->surname. '</h1>';
            echo '<span>'.  $user->email . '</span>';
            echo '<p>'.  $user->description . '</p>';
            echo '<pre>';
        }
        die();
    }
}
