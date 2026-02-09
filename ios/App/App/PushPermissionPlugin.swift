import Foundation
import Capacitor
import OneSignalFramework

@objc(PushPermissionPlugin)
public class PushPermissionPlugin: CAPPlugin {
    
    @objc func requestPermission(_ call: CAPPluginCall) {
        DispatchQueue.main.async {
            // Ensure OneSignal is initialized before requesting permission
            guard OneSignal.Notifications != nil else {
                NSLog("OneSignal not initialized")
                call.reject("OneSignal not initialized")
                return
            }
            
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
            // Ensure OneSignal is initialized before checking permission
            guard OneSignal.Notifications != nil else {
                NSLog("OneSignal not initialized")
                call.reject("OneSignal not initialized")
                return
            }
            
            let permission = OneSignal.Notifications.permission
            call.resolve([
                "permission": permission
            ])
        }
    }
}