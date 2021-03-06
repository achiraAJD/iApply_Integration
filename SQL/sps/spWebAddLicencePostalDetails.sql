USE [LGS_UAT]
GO
/****** Object:  StoredProcedure [dbo].[spWebAddLicencePostalDetails]    Script Date: 1/07/2022 11:45:28 AM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
-- =============================================
-- Author:		<Achira Warnakulasuriya>
-- Create date: <21/10/2021>
-- Description:	<Adding Licence Postal Details to LOGIC>
-- =============================================
ALTER PROCEDURE [dbo].[spWebAddLicencePostalDetails] 
	-- Add the parameters for the stored procedure here
	@LPD_Address1					VARCHAR(50) = NULL,
	@LPD_Address2					VARCHAR(50) = NULL,
	@LPD_State						VARCHAR(3) = NULL,
	@LPD_PostCode					VARCHAR(4) = NULL,
	@LPD_Town						VARCHAR(50) = NULL,
	@CreationDateTime				smalldatetime = NULL,
	@CreationUser					VARCHAR(50) = NULL,
	@LastUpdateDateTime				smalldatetime = NULL,
	@LastUpdateUser					VARCHAR(50) = NULL,
	@TimeStamp						timestamp = NULL,
	@LPD_Email						VARCHAR(255) = NULL,
	@LPD_MobileNotification			VARCHAR(15) = NULL,
	@LPD_CB_ID_PreferredDelivery	NUMERIC(18,0) = Null,
	@LPD_EmailLastReject			DATETIME = NULL,
	@LPD_SMSLastReject				DATETIME = NULL,	
	@LPD_SendSMSNotifications		BIT,
	@LIC_ID							NUMERIC(18,0)

	
AS
BEGIN
	SET NOCOUNT ON;

	DECLARE @lpd_id	NUMERIC(18,0)
		
	INSERT INTO LicencePostalDetails (LPD_Address1,LPD_Address2,LPD_State,LPD_PostCode,LPD_Town,CreationDateTime,CreationUser,LastUpdateDateTime,				
	LastUpdateUser,LPD_Email,LPD_MobileNotification,LPD_CB_ID_PreferredDelivery,LPD_EmailLastReject,LPD_SMSLastReject,LPD_SendSMSNotifications)	
	VALUES
	(@LPD_Address1,@LPD_Address2,@LPD_State,@LPD_PostCode,@LPD_Town,GETDATE(),@LastUpdateUser,GETDATE(),@LastUpdateUser,@LPD_Email,						
	@LPD_MobileNotification,@LPD_CB_ID_PreferredDelivery,@LPD_EmailLastReject,@LPD_SMSLastReject,@LPD_SendSMSNotifications	)

    SELECT @lpd_id = SCOPE_IDENTITY()

	--update Licenses table
	UPDATE Licences
	SET LIC_LPD_ID = @lpd_id
	WHERE LIC_ID = @LIC_ID 
	
	SELECT @lpd_id as LPD_ID
END
