<?php namespace OpenDominion\Http\Controllers;

class HomeController extends Controller {

	public function getIndex()
	{
		return view('home');
	}

}
