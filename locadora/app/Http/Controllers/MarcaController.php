<?php

namespace App\Http\Controllers;

use App\Models\Marca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MarcaController extends Controller
{

    protected $marca;

    public function __construct(Marca $marca)
    {
        $this->marca = $marca;
    }

    public function index()
    {
        $marcas = $this->marca->with('modelos')->get();
        return response()->json($marcas, 200);
    }

    public function store(Request $request)
    {

    $regras = [
        'nome' => 'required|unique:marcas,nome|min:3',
        'imagem' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
    ];

    $request->validate($regras);

    if($request->hasFile('imagem')) {
        //salva a imagem
        $imagem = $request->file('imagem');
        $caminho = $imagem->store('imagens', 'public'); //armazena na pasta public

        $caminhoCompleto = Storage::url($caminho); // retorna o caminho completo da imagem

        $marca = $this->marca->create([
            'nome' => $request->input('nome'),
            'imagem' => $caminhoCompleto,
        ]);
        return response()->json($marca, 201);
    } else {
        return response()->json(['erro' => 'Imagem não encontrada'], 400);
    }
    
    }


    public function show($id)
    {
        // $marca = Marca::findOrFail($id);
        // return response()->json($marca);
        $marca = $this->marca->with('modelos')->find($id);
        if($marca === null) {
            return response()->json(['erro' => 'Recurso pesquisado não existe'], 404);
        }
        return response()->json($marca, 200);
    }



    public function update(Request $request, $id)
    {
    $marca = $this->marca->findOrFail($id);

    $regras = [
        'nome' => 'sometimes|required|min:3|unique:marcas,nome,'.$id,
        'imagem' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    ];

    $request->validate($regras);

    $dadosAtualizados = $request->only('nome');

    // Se a imagem estiver sendo enviada, atualize-a
    if($request->hasFile('imagem')) {
        $imagem = $request->file('imagem');
        $caminho = $imagem->store('imagens', 'public');
        $dadosAtualizados['imagem'] = $caminho;
    }

    $marca->update($dadosAtualizados);

    return response()->json($marca, 200);
    }

    public function destroy($id)
    {
        // $marca = Marca::findOrFail($id);
        // $marca->delete();
        // return response()->json(null, 204);
        $marca = $this->marca->find($id);
        if($marca === null) {
            return response()->json(['erro' => 'Registro não existe para ser apagado'], 404);
        }

        // Remova a imagem do sistema de arquivos
        Storage::delete($marca->imagem);

        $marca->delete();
        return response()->json(['msg' => 'A marca foi removida com sucesso!'], 404);
    }
}
