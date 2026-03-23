<?php

use Livewire\Component;

new class extends Component
{
    public function render()
    {
        return $this->view()->title('Menu Management');
    }
};
?>

<div>
    <x-header header="Menu Management"
        description="Manage menu items, adjust base pricing to be used for all branches " />
</div>