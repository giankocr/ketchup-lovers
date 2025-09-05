/**
 * Wallet Access Control JavaScript
 * 
 * This file handles client-side wallet access control for the Ketchup Lovers theme
 * It disables wallet transfer and withdrawal links and redirects wallet links to transactions
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Disable wallet transfer links
    var transferLinks = document.querySelectorAll('a[href*="wallet-transfer"]');
    transferLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            alert(walletAccessData.accessDeniedMessage);
            return false;
        });
    });
    
    // Disable wallet withdrawal links
    var withdrawalLinks = document.querySelectorAll('a[href*="wallet-withdrawal"]');
    withdrawalLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            alert(walletAccessData.withdrawalDeniedMessage);
            return false;
        });
    });
    
    // Disable onclick functions for wallet tabs
    var walletTabs = document.querySelectorAll('.wallet-tabs .tabs li');
    walletTabs.forEach(function(tab) {
        if (tab.onclick && tab.onclick.toString().includes('enable_wallet_link')) {
            tab.onclick = function(e) {
                e.preventDefault();
                return false;
            };
        }
    });
    
    // Find all wallet links that are not transactions, transfer, or withdrawal
    var walletLinks = document.querySelectorAll('a[href*="wps-wallet"]');
    walletLinks.forEach(function(link) {
        var href = link.getAttribute('href');
        
        // Check if this is a main wallet link (not specific pages)
        if (href && 
            href.indexOf('wps-wallet') !== -1 &&
            href.indexOf('wallet-transfer') === -1 &&
            href.indexOf('wallet-withdrawal') === -1 &&
            href.indexOf('wallet-transactions') === -1) {
            
            // Change the link to point to transactions
            link.href = walletAccessData.homeUrl;
        }
    });
}); 