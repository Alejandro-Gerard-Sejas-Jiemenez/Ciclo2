@extends('layouts.panelAdmin')

@section('title', 'Registrar Compra')

@section('contenido')
@include('components.panelAdmin.header', [
'titulo' => 'Panel Admin',
'subtitulo' => 'Administración de Compras.',
])



@if ($errors->any())
<div class="mb-4 rounded bg-red-100 text-red-800 px-4 py-2">
    <ul class="list-disc pl-5">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form action="{{ route('compra.store') }}" method="POST" class="space-y-6">
    @csrf
    <div class="bg-white px-10 py-8 rounded-xl shadow-md max-w-5xl mx-auto">
        <div class="space-y-4">
            <h1 class="text-center text-2xl font-semibold text-gray-600">Registrar Nueva Compra</h1>

            <!-- Número de Compra -->
            <div>
                <label class="block mb-1 text-gray-600 font-semibold">Número de Compra</label>
                <div class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md w-max border border-gray-300 font-mono select-none">
                    {{ $numeroCompra }}
                </div>
                <input type="hidden" name="codigo_compra" value="{{ $numeroCompra }}">
            </div>

            <!-- Proveedor -->
            <div>
                <label for="id_proveedor" class="block mb-1 text-gray-600 font-semibold">Proveedor</label>
                <select name="id_proveedor" id="id_proveedor"
                    class="select2 bg-indigo-50 px-4 py-2 rounded-md w-full border border-blue-200">
                    <option value="">Seleccione un proveedor</option>
                    @foreach ($proveedores as $proveedor)
                    <option value="{{ $proveedor->id_proveedor }}" {{ old('id_proveedor') == $proveedor->id_proveedor ? 'selected' : '' }}>
                        {{ $proveedor->nombreC_proveedor }}
                    </option>
                    @endforeach
                </select>
            </div>
            <!-- Tabla de productos -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-gray-700 border border-gray-300 rounded">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-2 border">Producto</th>
                            <th class="p-2 border">Cantidad</th>
                            <th class="p-2 border">Precio</th>
                            <th class="p-2 border">Subtotal</th>
                            <th class="p-2 border">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="productos-lista">
                        <!-- Aquí se agregan productos dinámicamente con JS -->
                    </tbody>
                </table>
            </div>

            <button type="button" id="btn-agregar-producto"
                class="bg-green-500 text-white px-4 py-2 mt-2 rounded hover:bg-green-600 transition font-semibold">
                + Agregar Producto
            </button>

            <!-- Total -->
            <div class="text-right mt-4">
                <label class="text-gray-700 font-bold">Total Compra:</label>
                <span id="total-compra" class="text-xl font-semibold text-blue-600">0.00</span>
                <input type="hidden" name="total" id="total" value="0">
            </div>

            <!-- Observaciones -->
            <div>
                <label for="descripcion" class="block mb-1 text-gray-600 font-semibold">Descripcion</label>
                <textarea name="descripcion" id="descripcion" rows="3"
                    class="bg-indigo-50 px-4 py-2 rounded-md w-full border border-blue-200 focus:ring-2 focus:ring-blue-400 transition">{{ old('descripcion') }}</textarea>
            </div>
        </div>

        <!-- Botones -->
        <div class="flex justify-between mt-6">
            <button type="submit"
                class="bg-blue-600 text-white py-2 px-4 rounded-lg font-bold shadow hover:bg-blue-700 transition">
                Registrar Compra
            </button>
            <a href="{{ route('compra.index') }}"
                class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 px-4 rounded-lg transition shadow">
                Cancelar
            </a>
        </div>
    </div>
</form>


<!-- Scripts y estilos para Select2 -->
@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush
@push('scripts')

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2').select2({
            placeholder: 'Buscar...',
            allowClear: true
        });

        const productos = JSON.parse('@json($productos)');
        const proveedores = JSON.parse('@json($proveedores)');
        let contador = 0;
        console.log($('#btn-agregar-producto').length);
        $('#btn-agregar-producto').off('click').on('click', function () {
           
            let index = Date.now(); // índice único
            let fila = `
        <tr>
            <td class="p-2 border">
                <select name="productos[${index}][id_producto]" class="select2-producto w-full">
                    <option value="">Seleccione</option>
                    ${productos.map(p => `<option value="${p.id_producto}">${p.nombre_producto} - ${p.descripcion}</option>`).join('')}

                </select>
            </td>
            <td class="p-2 border">
                <input type="number" name="productos[${index}][cantidad]" min="1" value="1"
                       class="cantidad w-full border px-2 py-1" />
            </td>
            <td class="p-2 border">
                <input type="number" name="productos[${index}][precio]" min="0" step="0.01" value="0"
                       class="precio w-full border px-2 py-1" />
            </td>
            <td class="p-2 border text-right">
                <span class="subtotal">0.00</span>
            </td>
            <td class="p-2 border text-center">
                <button type="button" class="eliminar-fila text-red-600 hover:text-red-800 font-bold">X</button>
            </td>
        </tr>
    `;

            $('#productos-lista').append(fila);

            // ✅ Inicializar sólo el último select agregado
            $('#productos-lista .select2-producto').last().select2({
                placeholder: 'Producto',
                allowClear: true
            });

            actualizarTotales();
        });

        $(document).on('input', '.cantidad, .precio', function() {
            actualizarTotales();
        });

        $(document).on('click', '.eliminar-fila', function() {
            $(this).closest('tr').remove();
            actualizarTotales();
        });

        function actualizarTotales() {
            let total = 0;
            $('#productos-lista tr').each(function() {
                let cantidad = parseFloat($(this).find('.cantidad').val()) || 0;
                let precio = parseFloat($(this).find('.precio').val()) || 0;
                let subtotal = cantidad * precio;
                $(this).find('.subtotal').text(subtotal.toFixed(2));
                total += subtotal;
            });
            $('#total-compra').text(total.toFixed(2));
            $('#total').val(total.toFixed(2));
        }
    });
</script>
@endpush

@endsection
