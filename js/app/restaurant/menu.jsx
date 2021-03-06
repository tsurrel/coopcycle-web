import React from 'react';
import { render } from 'react-dom';
import Switch from 'antd/lib/switch';
import dragula from 'dragula';

function collapseAll() {
  $('#menu_sections .panel-collapse').collapse('hide');
}

function refreshPositions() {
  const menuItems = [].slice.call(document.querySelectorAll('[data-menu-item]'))
  menuItems.forEach((menuItem, index) => menuItem.querySelector('[data-position]').value = (index + 1))
}

function addMenuItemForm($container) {

  var prototype = $container.data('prototype');
  var index = $container.children().length;

  var form = prototype.replace(/__items__/g, index);
  var $form = $(form);

  $container.append($form);
  refreshPositions();
  renderSwitch($form.find('.switch'));

  if (!$container.closest('.collapse').hasClass('in')) {
    collapseAll();
    $container.closest('.collapse').collapse('show');
  }
  $form.find('> .collapse').collapse('show');
}

function addMenuItemModifierGroupForm($container) {

  var prototype = $container.data('prototype');
  var index = $container.children().length;

  var form = prototype.replace(/__modifiers__/g, index);
  var $form = $(form);

  $container.append($form);
}

function addMenuItemModifierForm($container) {

  var prototype = $container.data('prototype');
  var index = $container.children().length;

  var form = prototype.replace(/__modifierChoices__/g, index);
  var $form = $(form);

  $container.append($form);
}

function renderCalculusStrategy($input) {

  const $groupPrice = $input.closest('.form-group').find('.modifier-group-price');
  const $groupPriceLabel = $input.closest('.form-group').find('.modifier-group-price-label');
  const $modifiersPrice = $input.closest('.menu-item-modifiers').find('.modifier-price');

  switch($input.val()) {
    case 'FREE':
      $groupPrice.closest('.input-group').addClass('hidden');
      $groupPrice.val(0.00);
      $modifiersPrice.closest('.input-group').addClass('hidden');

      $groupPrice.prop('required', false);
      $modifiersPrice.prop('required', false);

      $groupPriceLabel.addClass('hidden');
      break;
    case 'ADD_MENUITEM_PRICE':
      $groupPrice.closest('.input-group').addClass('hidden');
      $groupPrice.val(0.00);
      $modifiersPrice.closest('.input-group').removeClass('hidden');

      $groupPrice.prop('required', false);
      $modifiersPrice.prop('required', true);

      $groupPriceLabel.addClass('hidden');
      break;
    case 'ADD_MODIFIER_PRICE':
      $groupPrice.closest('.input-group').removeClass('hidden');
      $modifiersPrice.closest('.input-group').addClass('hidden');

      $groupPrice.prop('required', true);
      $modifiersPrice.prop('required', false);

      $groupPriceLabel.removeClass('hidden');
      break;
  }
}

function renderSwitch($input) {

  const $parent = $input.closest('div.checkbox').parent();

  const $switch = $('<div>');
  const $hidden = $('<input>')

  $switch.addClass('switch');

  $hidden
    .attr('type', 'hidden')
    .attr('name', $input.attr('name'))
    .attr('value', $input.attr('value'));

  $parent.append($switch);
  $parent.append($hidden);

  const checked = $input.is(':checked');

  $input.closest('div.checkbox').remove();

  render(
    <Switch defaultChecked={ checked }
      checkedChildren={ window.__i18n['Available'] } unCheckedChildren={ window.__i18n['Unavailable'] }
      onChange={(checked) => {
        if (checked) {
          $parent.append($hidden);
        } else {
          $hidden.remove();
        }
      }} />,
    $switch.get(0)
  );

}

function initFormControls() {
  const $form = $('form[name="menu"]')
  $('body').tooltip({
    selector: '[data-toggle="tooltip"]'
  })
  $form.find('.modifier-calculus-strategy').each((index, input) => renderCalculusStrategy($(input)));
  $form.find('.switch').each((index, el) => renderSwitch($(el)));
}

function enableForm($form, enable) {
  if (enable) {
    $('#menu_sectionName').removeAttr('disabled');
    $form.find('[type="submit"]').removeAttr('disabled');
  } else {
    $('#menu_sectionName').attr('disabled', true);
    $form.find('[type="submit"]').attr('disabled', true);
  }
}

function autoDismissMessages() {
  setTimeout(function() {
    $('#messages .alert').fadeOut();
  }, 1000);
}

$(function() {

  var $form = $('form[name="menu"]');

  autoDismissMessages();
  initFormControls();

  $(document).on('click', '.close', function(e) {
    e.preventDefault();
    var selector = $(e.target).closest('.close').data('target');
    $(selector).remove();
    refreshPositions();
  });

  $(document).on('click', '[data-toggle="add-menu-item"]', function(e) {
    e.preventDefault();
    var selector = $(e.target).data('target');
    var $target = $(selector);
    addMenuItemForm($target);
    $target.find('.switch').each((index, el) => renderSwitch($(el)));
  });

  $(document).on('click', '[data-toggle="add-menu-item-modifier"]', function(e) {
    e.preventDefault();
    var $target = $(e.target).prev();
    addMenuItemModifierForm($target);
    var $input = $target.closest('.menu-item-modifiers').find('.modifier-calculus-strategy');
    renderCalculusStrategy($input);
  });

  $(document).on('click', '[data-toggle="add-menu-item-modifier-group"]', function(e) {
    e.preventDefault();
    e.preventDefault();
    var selector = $(e.target).data('target');
    var $target = $(selector);
    addMenuItemModifierGroupForm($target);
    var $input = $target.find('select.modifier-calculus-strategy');
    renderCalculusStrategy($input);
  });

  $(document).on('click', '[data-toggle="remove-menu-section"]', function(e) {
    e.preventDefault();

    var selector = $(e.target).data('target');
    var $target = $(selector);

    var $copy = $target.detach();

    var data = $form.serialize();
    enableForm($form, false);

    $.ajax({
      url : $form.attr('action'),
      type: $form.attr('method'),
      data : data
    })
    .then(function(html) {

      enableForm($form, true);
      $copy.remove();

      $('#messages').replaceWith(
        $(html).find('#messages')
      );
      $('#menu_sections').replaceWith(
        $(html).find('#menu_sections')
      );

      autoDismissMessages();
      initFormControls();

    })
    .catch(function(e) {
      enableForm($form, true);
    });
  });

  $(document).on('show.bs.collapse', '[role="tabpanel"] .list-group-item .collapse', function () {
    $(this).closest('.list-group-item').find('.show-description').remove();
  });

  $(document).on('change', 'select.modifier-calculus-strategy', function(e) {
    renderCalculusStrategy($(this));
  })

  $('#menu_save').on('click', function(e) {
    $('#menu_sectionName').removeAttr('required')
  })

  if (window.AppData.MenuForm.newSections.length > 0) {
    window.AppData.MenuForm.newSections.forEach(section => {
      const $el = $(`[data-section-id="${section.id}"]`);
      addMenuItemForm($('#' + $el.attr('id') + '_items'));
    })
  }

  const drakeContainers = [].slice.call(document.querySelectorAll('.menuForm__menuItems'))

  const drake = dragula(drakeContainers, {
    moves: (el, container, handle) => {
      return handle.classList.contains('menuForm__menuItem__heading__arrows')
        || handle.parentNode.classList.contains('menuForm__menuItem__heading__arrows')
    }
  })
  .on('dragend', el => refreshPositions(el.parentNode))

});
