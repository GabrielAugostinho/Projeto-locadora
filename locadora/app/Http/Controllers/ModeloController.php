<?php

namespace App\Http\Controllers;

use App\Models\Modelo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ModeloController extends Controller
{

    protected $modelo;

    public function __construct(Modelo $modelo)
    {
        $this->modelo = $modelo;
    }
   
    public function index(Request $request)
    {

        $modelos = array();

        if($request->has('atributos_marca')) {
            $atributos_marca = $request->atributos_marca;
            $modelos = $this->modelo->with('marca:id,'.$atributos_marca);
        } else {
            $modelos = $this->modelo->with('marca');
        }

        if($request->has('filtros')) {
            $atributos = $request->atributos;
            $modelos = $modelos->selectRaw($atributos)->get();
        } 

        if($request->has('atributos')) {
            $condicoes = explode(':', $request->filtro);
            $modelos = $modelos->where($condicoes[0], $condicoes[1], $condicoes[2]);
        } else {
            $modelos = $modelos->get();
        }
        return response()->json($modelos, 200);
    }

    public function store(Request $request)
    {
        $regras = [
            'marca_id' => 'required|exists:marcas,id',
            'nome' => 'required|unique:modelos,nome|min:3', //o $this->
            'imagem' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'numero_portas' => 'required|integer|digits_between:1,5',
            'lugares' => 'required|integer|digits_between:1,20',
            'air_bag' => 'required|boolean',
            'abs' => 'required|boolean',

        ];
    
        $request->validate($regras);
    
        if($request->hasFile('imagem')) {
            //salva a imagem
            $imagem = $request->file('imagem');
            $caminho = $imagem->store('imagens/modelos', 'public'); //armazena na pasta public
    
            $caminhoCompleto = Storage::url($caminho); // retorna o caminho completo da imagem
    
            $modelo = $this->modelo->create([
                'marca_id' => $request->marca_id,
                'nome' => $request->input('nome'),
                'imagem' => $caminhoCompleto,
                'numero_portas'=> $request->input('numero_portas'),
                'lugares'=> $request->input('lugares'),
                'air_bag'=> $request->input('air_bag'),
                'abs'=> $request->input('abs'),
            ]);
            return response()->json($modelo, 201);
        } else {
            return response()->json(['erro' => 'Imagem não encontrada'], 404);
        }
        
    }

    public function show($id)
    {
        $modelo = $this->modelo->with('marca')->find($id);
        if($modelo === null) {
            return response()->json(['erro' => 'Recurso pesquisado não existe'], 404);
        }
        return response()->json($modelo, 200);
    }

    public function update(Request $request, $id)
    {
        $modelo = $this->modelo->findOrFail($id);

        $regras = [
            'nome' => 'sometimes|required|min:3|unique:modelos,nome,'.$id,
            'imagem' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'numero_portas' => 'sometimes|required|integer|digits_between:1,5',
            'lugares' => 'sometimes|required|integer|digits_between:1,20',
            'air_bag' => 'sometimes|required|boolean',
            'abs' => 'sometimes|required|boolean',
        ];
    
        $request->validate($regras);
    
        $dadosAtualizados = $request->only('nome', 'numero_portas', 'lugares', 'air_bag', 'abs');
    
        // Se a imagem estiver sendo enviada, atualize-a
        if($request->hasFile('imagem')) {
            $imagem = $request->file('imagem');
            $caminho = $imagem->store('imagens/modelos', 'public');
            $dadosAtualizados['imagem'] = $caminho;
        }
    
        $modelo->update($dadosAtualizados);
    }

    public function destroy($id)
    {
        $modelo = $this->modelo->find($id);
        if($modelo === null) {
            return response()->json(['erro' => 'Registro não existe para ser apagado'], 404);
        }

        // Remova a imagem do sistema de arquivos
        Storage::delete($modelo->imagem);

        $modelo->delete();
        return response()->json(['msg' => 'O modelo foi removida com sucesso!'], 404);
    }
}
