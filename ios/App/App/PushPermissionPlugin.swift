import Foundation
import Capacitor
import OneSignalFramework

@objc(PushPermissionPlugin)
public class PushPermissionPlugin: CAPPlugin {
    
    @objc func requestPermission(_ call: CAPPluginCall) {
        DispatchQueue.main.async {
            // Note: OneSignal.Notifications exists as a namespace even if not initialized
            // The requestPermission call itself will handle the case where OneSignal isn't ready
            OneSignal.Notifications.requestPermission({ accepted in
                NSLog("OneSignal permission accepted: \(accepted)")
                call.resolve([
                    "accepted": accepted
                ])
            }, fallbackToSettings: true)
        }
    }
    
    @objc func checkPermission(_ call: CAPPluginCall) {
        DispatchQueue.main.async {
            // Note: OneSignal.Notifications.permission returns the current permission state
            // This is safe to call even if OneSignal isn't fully initialized
            let permission = OneSignal.Notifications.permission
            call.resolve([
                "permission": permission
            ])
        }
    }
}