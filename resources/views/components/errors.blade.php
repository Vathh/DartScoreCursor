@if($errors->any())
    <ul class="px-4 py-2 border-2 rounded border-light-red text-light-red mt-8">
        @foreach($errors->all() as $error)
            <li class="my-2 mb-2 text-light-red">{{ $error }}</li>
        @endforeach
    </ul>
@endif
