/*=========================================================================================
	File Name: tour.js
	Description: tour
	----------------------------------------------------------------------------------------
	Item Name: Vuexy  - Vuejs, HTML & Laravel Admin Dashboard Template
	Author: Pixinvent
	Author URL: hhttp://www.themeforest.net/user/pixinvent
==========================================================================================*/

$(function() {
    'use strict';

    var startBtn = $('#tour');

    function scrollUp() {

        var scroll_pos = 0;
        scroll_pos = document.getElementsByClassName('main-menu-content').offsetTop + 500;

        window.scrollTo({
            top: scroll_pos,
            behavior: 'smooth'
        });
        // console.log('Hello Nigeria' + scroll_pos);

        return;
    }

    function setupTour(tour) {
        var backBtnClass = 'btn btn-sm btn-outline-primary',
            nextBtnClass = 'btn btn-sm btn-primary btn-next';
        if ($('.nav-item.nav-toggle').length) {
            tour.addStep({
                title: 'Toggle Navigation',
                text: 'This gives you opportunity to toggle or Collapse this left Side Navigation by clicking on the circle icon',
                // text: 'This gives you opportunity to know your <b>Role</b>, Switch to <b>Dark mode</b> and <b>Change your Password</b> by clicking on the <b>Profile Icon</b>',
                attachTo: { element: '.nav-item.nav-toggle', on: 'bottom' },
                buttons: [{
                        action: tour.cancel,
                        classes: backBtnClass,
                        text: 'Skip'
                    },
                    {
                        text: 'Next',
                        classes: nextBtnClass,
                        action: tour.next
                    }
                ]
            });
        }
        if ($('.switch-moon').length) {
            tour.addStep({
                title: 'Turn ON/OFF Dark Mode',
                text: 'This gives you opportunity to Switch between <b>Dark mode</b> or <b>Light Mode</b>',
                // text: 'This gives you opportunity to know your <b>Role</b>, Switch to <b>Dark mode</b> and <b>Change your Password</b> by clicking on the <b>Profile Icon</b>',
                attachTo: { element: '.switch-moon', on: 'bottom' },
                buttons: [{
                        action: tour.cancel,
                        classes: backBtnClass,
                        text: 'Skip'
                    },
                    {
                        text: 'Next',
                        classes: nextBtnClass,
                        action: tour.next
                    }
                ]
            });
        }
        if ($('.e-Netcard').length > 0) {
            tour.addStep({
                title: 'e-Netcard Module',
                text: 'This Modules contains all <b>e-Netcard</b> functionalities, ranging from:<br> <b>Home</b>: This shows the <span class="text-danger">e-Netcard statistics</span> at a glance.<br> <b>e-Netcard Movement</b>: This handles e-Netcard transfer from <span class="text-danger">State</span> to <span class="text-danger">LGA</span> to <span class="text-danger">Ward</span> and the transfered e-Netcards can also be <b>Reversed</b> from <span class="text-danger">Ward</span> to <span class="text-danger">LGA</span> to <span class="text-danger">State</span><br> <b>e-Netcard Allocation</b>: This handles both e-Netcard <span class="text-danger">Transfer</span> and <span class="text-danger">Reversal</span> from and to HHM',
                attachTo: { element: '.e-Netcard', on: 'right' },
                buttons: [{
                        text: 'Skip',
                        classes: backBtnClass,
                        action: tour.cancel
                    },
                    {
                        text: 'Back',
                        classes: backBtnClass,
                        action: tour.back
                    },
                    {
                        text: 'Next',
                        classes: nextBtnClass,
                        action() {
                            scrollUp();
                            return this.next();
                        }
                    }
                ]
            });
        }
        if ($('.Users').length > 0) {
            tour.addStep({
                title: 'Users Module',
                text: 'This Modules contains all <b>User management</b>  functionalities. Ranging from:<br> <b>Home</b>: This shows the Users distributions <span class="text-danger">statistic</span> <br> <b>Users List</b>: This shows the List of all Users and the actions that can be performed on them ranging from <span class="text-danger">{User Details, Change Geo Location, Change User Role, Reset User Password, De/Activate User, Download Badge} </span><br><b>User Group</b>:This is avail you the opportunity to Create User by Group, User De/Activation by group, User Badge download by group',
                attachTo: { element: '.Users', on: 'right' },
                buttons: [{
                        text: 'Skip',
                        classes: backBtnClass,
                        action: tour.cancel
                    },
                    {
                        text: 'Back',
                        classes: backBtnClass,
                        action: tour.back
                    },
                    {
                        text: 'Next',
                        classes: nextBtnClass,
                        action: tour.next
                    }
                ]
            });
        }
        if ($('.Training').length > 0) {
            tour.addStep({
                title: 'Training Module',
                text: 'This Modules contains Training Creation, Training Session Creation, De/Activation of Training, Adding of Participants to Training and Training Session Attendance Viewing',
                attachTo: { element: '.Training', on: 'right' },
                buttons: [{
                        text: 'Skip',
                        classes: backBtnClass,
                        action: tour.cancel
                    },
                    {
                        text: 'Back',
                        classes: backBtnClass,
                        action: tour.back
                    },
                    {
                        text: 'Next',
                        classes: nextBtnClass,
                        action: tour.next
                    }
                ]
            });
        }
        if ($('.Mobilization').length > 0) {
            tour.addStep({
                title: 'Mobilization Module',
                text: 'This Modules contains Mobilization Lists; List of mobilized households and the alloted Nets, Mobilization Statistics Per LGA/Ward',
                attachTo: { element: '.Mobilization', on: 'right' },
                buttons: [{
                        text: 'Skip',
                        classes: backBtnClass,
                        action: tour.cancel
                    },
                    {
                        text: 'Back',
                        classes: backBtnClass,
                        action: tour.back
                    },
                    {
                        text: 'Next',
                        classes: nextBtnClass,
                        action: tour.next
                    }
                ]
            });
        }
        if ($('.Distribution').length > 0) {
            tour.addStep({
                title: 'Distribution Module',
                text: 'This Modules contain Distribution Point Lists with capability to dowbload each DP badge, and Netcards distributed to households',
                attachTo: { element: '.Distribution', on: 'right' },
                buttons: [{
                        text: 'Skip',
                        classes: backBtnClass,
                        action: tour.cancel
                    },
                    {
                        text: 'Back',
                        classes: backBtnClass,
                        action: tour.back
                    },
                    {
                        text: 'Next',
                        classes: nextBtnClass,
                        action: tour.next
                    }
                ]
            });
        }
        if ($('.nav-item.Info').length > 0) {
            tour.addStep({
                title: 'Info',
                text: 'This containg the System SOP',
                attachTo: { element: '.nav-item.Info', on: 'top' },
                buttons: [{
                        text: 'Back',
                        classes: backBtnClass,
                        action: tour.back
                    },
                    {
                        text: 'Finish',
                        classes: nextBtnClass,
                        action: tour.cancel
                    }
                ]
            });
        }

        return tour;
    }

    if (startBtn.length) {
        startBtn.on('click', function() {
            var tourVar = new Shepherd.Tour({
                defaultStepOptions: {
                    classes: 'shadow-md bg-purple-dark',
                    scrollTo: false,
                    cancelIcon: {
                        enabled: true
                    }
                },
                useModalOverlay: true
            });

            setupTour(tourVar).start();
        });
    }
});